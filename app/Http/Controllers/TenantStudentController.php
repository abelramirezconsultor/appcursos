<?php

namespace App\Http\Controllers;

use App\Models\CourseLesson;
use App\Models\CourseEnrollment;
use App\Models\CourseProgress;
use App\Models\ActivationCode;
use App\Models\EnrollmentLessonProgress;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\UserGamificationStat;
use App\Models\UserCourseAchievement;
use App\Models\User;
use App\Notifications\Tenant\CourseCompletedNotification;
use App\Notifications\Tenant\StreakReminderNotification;
use App\Services\Gamification\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TenantStudentController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($this->isTenantSuspended()) {
            return redirect()->route('tenant.student.login', [
                'tenant' => $this->tenantRouteKey(),
            ])->withErrors([
                'email' => 'El tenant está suspendido. Contacta al administrador.',
            ]);
        }

        $tenantId = $this->tenantId();
        $tenantRouteKey = $this->tenantRouteKey();
        $sessionTenantId = (string) $request->session()->get('tenant_student_tenant_id', '');
        $studentId = (int) $request->session()->get('tenant_student_user_id', 0);

        if ($studentId <= 0 || $sessionTenantId !== $tenantId) {
            return redirect()->route('tenant.student.login', [
                'tenant' => $tenantRouteKey,
            ])->with('status', 'Inicia sesión como estudiante para continuar.');
        }

        $student = User::query()->find($studentId);

        if (! $student || ! $student->hasRole('student')) {
            $request->session()->forget([
                'tenant_student_user_id',
                'tenant_student_tenant_id',
            ]);

            return redirect()->route('tenant.student.login', [
                'tenant' => $tenantRouteKey,
            ])->withErrors([
                'email' => 'Tu sesión de estudiante ya no es válida.',
            ]);
        }

        $enrollments = CourseEnrollment::query()
            ->with([
                'course:id,title,status',
                'progress:id,enrollment_id,progress_percent,last_viewed_at,completed_at',
            ])
            ->where('user_id', $student->id)
            ->orderByDesc('id')
            ->get();

        $enrolledCourseIds = $enrollments->pluck('course_id')->map(fn ($id) => (int) $id)->all();

        $assignedCodes = ActivationCode::query()
            ->with('course:id,title,status')
            ->where('assigned_user_id', $student->id)
            ->whereNotIn('course_id', $enrolledCourseIds)
            ->orderByDesc('id')
            ->get(['id', 'course_id', 'code', 'is_active', 'expires_at', 'uses_count', 'max_uses'])
            ->map(function (ActivationCode $code): array {
                return [
                    'id' => $code->id,
                    'code' => $code->code,
                    'course_title' => $code->course?->title,
                    'course_status' => $code->course?->status,
                    'is_active' => (bool) $code->is_active,
                    'expires_at' => optional($code->expires_at)?->format('Y-m-d H:i'),
                    'uses' => $code->uses_count . '/' . $code->max_uses,
                ];
            });

        $notifications = $student->notifications()
            ->latest()
            ->limit(15)
            ->get()
            ->map(function (DatabaseNotification $notification): array {
                $data = (array) $notification->data;

                return [
                    'id' => $notification->id,
                    'title' => (string) ($data['title'] ?? 'Notificación'),
                    'message' => (string) ($data['message'] ?? ''),
                    'category' => (string) ($data['category'] ?? 'general'),
                    'is_read' => ! is_null($notification->read_at),
                    'created_at' => optional($notification->created_at)?->format('Y-m-d H:i'),
                ];
            });

        $courseAchievements = UserCourseAchievement::query()
            ->where('user_id', $student->id)
            ->orderByDesc('awarded_at')
            ->get(['id', 'course_id', 'slug', 'name', 'awarded_at'])
            ->groupBy('course_id')
            ->map(fn ($items) => $items->map(fn (UserCourseAchievement $achievement) => [
                'id' => $achievement->id,
                'slug' => $achievement->slug,
                'name' => $achievement->name,
                'awarded_at' => optional($achievement->awarded_at)?->format('Y-m-d H:i'),
            ])->values()->all())
            ->toArray();

        return view('tenant.my-courses', [
            'student' => $student,
            'enrollments' => $enrollments,
            'assignedCodes' => $assignedCodes,
            'notifications' => $notifications,
            'courseAchievements' => $courseAchievements,
            'gamificationStat' => UserGamificationStat::query()->where('user_id', $student->id)->first(),
            'badges' => $student->badges()->orderBy('name')->get(['badges.id', 'badges.name']),
            'tenantRouteKey' => $tenantRouteKey,
        ]);
    }

    public function show(Request $request, CourseEnrollment $enrollment): View|RedirectResponse
    {
        if ($this->isTenantSuspended()) {
            return redirect()->route('tenant.student.login', [
                'tenant' => $this->tenantRouteKey(),
            ])->withErrors([
                'email' => 'El tenant está suspendido. Contacta al administrador.',
            ]);
        }

        $guard = $this->resolveStudentContext($request);

        if ($guard instanceof RedirectResponse) {
            return $guard;
        }

        $student = $guard;

        if ((int) $enrollment->user_id !== (int) $student->id) {
            abort(403);
        }

        $enrollment->load([
            'course.modules' => fn ($query) => $query->orderBy('position'),
            'course.modules.quiz:id,course_id,module_id,title,minimum_score,max_attempts,is_active',
            'course.modules.lessons' => fn ($query) => $query->orderBy('position'),
            'progress',
            'lessonProgress:enrollment_id,lesson_id,progress_percent,viewed_at,completed_at',
        ]);

        $lessons = $this->flattenLessons($enrollment);
        $moduleUnlockMap = $this->buildModuleUnlockMap($enrollment);
        $lockedLessonIds = $lessons
            ->filter(fn ($lesson) => ! ($moduleUnlockMap[(int) $lesson->module_id] ?? false))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $completedLessonIds = $enrollment->lessonProgress
            ->filter(fn ($progress) => ! is_null($progress->completed_at))
            ->pluck('lesson_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $selectedLesson = $this->resolveSelectedLesson($request, $lessons, $completedLessonIds, $lockedLessonIds);

        [$progressPercent, $isCompleted] = $this->calculateProgress($lessons->count(), count($completedLessonIds));

        return view('tenant.course-detail', [
            'tenantRouteKey' => $this->tenantRouteKey(),
            'student' => $student,
            'enrollment' => $enrollment,
            'lessons' => $lessons,
            'completedLessonIds' => $completedLessonIds,
            'lockedLessonIds' => $lockedLessonIds,
            'selectedLesson' => $selectedLesson,
            'selectedVideoEmbedUrl' => $this->buildEmbedUrl($selectedLesson?->video_url),
            'moduleUnlockMap' => $moduleUnlockMap,
            'progressPercent' => $progressPercent,
            'isCompleted' => $isCompleted,
            'gamificationStat' => UserGamificationStat::query()->where('user_id', $student->id)->first(),
            'badges' => $student->badges()->orderBy('name')->get(['badges.id', 'badges.name']),
            'courseAchievements' => UserCourseAchievement::query()
                ->where('user_id', $student->id)
                ->where('course_id', $enrollment->course_id)
                ->orderByDesc('awarded_at')
                ->get(['id', 'slug', 'name', 'awarded_at']),
        ]);
    }

    public function completeLesson(Request $request, CourseEnrollment $enrollment, CourseLesson $lesson): RedirectResponse
    {
        if ($this->isTenantSuspended()) {
            return redirect()->route('tenant.student.login', [
                'tenant' => $this->tenantRouteKey(),
            ])->withErrors([
                'email' => 'El tenant está suspendido. Contacta al administrador.',
            ]);
        }

        $guard = $this->resolveStudentContext($request);

        if ($guard instanceof RedirectResponse) {
            return $guard;
        }

        if ((int) $enrollment->user_id !== (int) $guard->id) {
            abort(403);
        }

        $enrollment->load([
            'course.modules' => fn ($query) => $query->orderBy('position'),
            'course.modules.quiz:id,course_id,module_id,title,minimum_score,max_attempts,is_active',
        ]);

        $moduleUnlockMap = $this->buildModuleUnlockMap($enrollment);
        if (! ($moduleUnlockMap[(int) $lesson->module_id] ?? false)) {
            return redirect()->route('tenant.student.courses.show', [
                'tenant' => $this->tenantRouteKey(),
                'enrollment' => $enrollment->id,
            ])->withErrors([
                'lesson' => 'Esta lección está bloqueada por un prerequisito obligatorio del módulo.',
            ]);
        }

        $belongsToEnrollmentCourse = $lesson->module()
            ->whereHas('course', fn ($query) => $query->where('id', $enrollment->course_id))
            ->exists();

        if (! $belongsToEnrollmentCourse) {
            abort(404);
        }

        $result = DB::transaction(function () use ($enrollment, $lesson, $guard): array {
            $lessonProgress = EnrollmentLessonProgress::query()->firstOrNew(
                [
                    'enrollment_id' => $enrollment->id,
                    'lesson_id' => $lesson->id,
                ]
            );

            $alreadyCompleted = ! is_null($lessonProgress->completed_at);

            $lessonProgress->fill([
                'progress_percent' => 100,
                'viewed_at' => now(),
                'completed_at' => now(),
            ]);
            $lessonProgress->save();

            $totalLessons = CourseLesson::query()
                ->whereHas('module', fn ($query) => $query->where('course_id', $enrollment->course_id))
                ->count();

            $completedLessons = EnrollmentLessonProgress::query()
                ->where('enrollment_id', $enrollment->id)
                ->whereNotNull('completed_at')
                ->count();

            [$progressPercent, $isCompleted] = $this->calculateProgress($totalLessons, $completedLessons);

            CourseProgress::query()->updateOrCreate(
                [
                    'enrollment_id' => $enrollment->id,
                ],
                [
                    'lesson_id' => $lesson->id,
                    'progress_percent' => $progressPercent,
                    'last_viewed_at' => now(),
                    'completed_at' => $isCompleted ? now() : null,
                ]
            );

            $enrollment->update([
                'status' => $isCompleted ? 'completed' : 'active',
            ]);

            if ($alreadyCompleted) {
                return [
                    'xp_gain' => 0,
                    'streak_days' => 0,
                    'is_course_completed' => $isCompleted,
                    'new_badges' => [],
                ];
            }

            $gamification = app(GamificationService::class)->registerLessonCompletion(
                $guard,
                (int) ($lesson->xp_reward ?? 0),
                (int) $enrollment->course_id,
                $progressPercent,
                $completedLessons,
                $totalLessons
            );

            return [
                ...$gamification,
                'is_course_completed' => $isCompleted,
            ];
        });

        $message = 'Lección marcada como completada.';
        if (($result['xp_gain'] ?? 0) > 0) {
            $message .= ' +' . $result['xp_gain'] . ' XP.';
        }
        if (! empty($result['new_badges'])) {
            $message .= ' Insignias desbloqueadas: ' . implode(', ', $result['new_badges']) . '.';
        }
        if (! empty($result['new_course_achievements'])) {
            $message .= ' Logros por curso: ' . implode(', ', $result['new_course_achievements']) . '.';
        }

        if (! empty($result['is_course_completed'])) {
            $guard->notify(new CourseCompletedNotification((string) ($enrollment->course?->title ?? 'Curso')));
        }

        if (($result['streak_days'] ?? 0) > 0) {
            $this->registerStreakReminderIfNeeded($guard, (int) $result['streak_days']);
        }

        return redirect()->route('tenant.student.courses.show', [
            'tenant' => $this->tenantRouteKey(),
            'enrollment' => $enrollment->id,
            'lesson' => $lesson->id,
        ])->with('success', $message);
    }

    public function showQuiz(Request $request, CourseEnrollment $enrollment, Quiz $quiz): View|RedirectResponse
    {
        $guard = $this->resolveStudentContext($request);

        if ($guard instanceof RedirectResponse) {
            return $guard;
        }

        if ((int) $enrollment->user_id !== (int) $guard->id) {
            abort(403);
        }

        $quiz->load([
            'questions' => fn ($query) => $query->orderBy('position'),
            'questions.options' => fn ($query) => $query->orderBy('position'),
        ]);

        if ((int) $quiz->course_id !== (int) $enrollment->course_id || ! $quiz->is_active) {
            abort(404);
        }

        $latestAttempt = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('enrollment_id', $enrollment->id)
            ->orderByDesc('attempted_at')
            ->first();

        return view('tenant.quiz', [
            'tenantRouteKey' => $this->tenantRouteKey(),
            'enrollment' => $enrollment,
            'quiz' => $quiz,
            'latestAttempt' => $latestAttempt,
        ]);
    }

    public function submitQuiz(Request $request, CourseEnrollment $enrollment, Quiz $quiz): RedirectResponse
    {
        $guard = $this->resolveStudentContext($request);

        if ($guard instanceof RedirectResponse) {
            return $guard;
        }

        if ((int) $enrollment->user_id !== (int) $guard->id) {
            abort(403);
        }

        if ((int) $quiz->course_id !== (int) $enrollment->course_id || ! $quiz->is_active) {
            abort(404);
        }

        $quiz->load([
            'questions' => fn ($query) => $query->orderBy('position'),
            'questions.options' => fn ($query) => $query->orderBy('position'),
        ]);

        $attemptsCount = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('enrollment_id', $enrollment->id)
            ->count();

        if (! is_null($quiz->max_attempts) && $attemptsCount >= (int) $quiz->max_attempts) {
            return back()->withErrors([
                'quiz' => 'Alcanzaste el máximo de intentos para esta evaluación.',
            ]);
        }

        $answers = (array) $request->input('answers', []);
        $answeredCount = collect($answers)
            ->filter(fn ($value) => ! is_null($value) && $value !== '')
            ->count();

        if ($answeredCount < 1) {
            return back()->withErrors([
                'quiz' => 'Debes responder al menos una pregunta para enviar el quiz.',
            ])->withInput();
        }

        $result = DB::transaction(function () use ($quiz, $enrollment, $guard, $answers): array {
            $totalQuestions = $quiz->questions->count();
            $correctAnswers = 0;

            $attempt = QuizAttempt::query()->create([
                'quiz_id' => $quiz->id,
                'enrollment_id' => $enrollment->id,
                'user_id' => $guard->id,
                'total_questions' => $totalQuestions,
                'correct_answers' => 0,
                'score_percent' => 0,
                'status' => 'rejected',
                'attempted_at' => now(),
            ]);

            foreach ($quiz->questions as $question) {
                $selectedOptionId = isset($answers[$question->id]) ? (int) $answers[$question->id] : null;

                /** @var QuizOption|null $selectedOption */
                $selectedOption = $selectedOptionId
                    ? $question->options->firstWhere('id', $selectedOptionId)
                    : null;

                $isCorrect = (bool) ($selectedOption?->is_correct ?? false);
                if ($isCorrect) {
                    $correctAnswers++;
                }

                QuizAnswer::query()->create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'selected_option_id' => $selectedOption?->id,
                    'is_correct' => $isCorrect,
                ]);
            }

            $scorePercent = $totalQuestions > 0
                ? (int) round(($correctAnswers / $totalQuestions) * 100)
                : 0;

            $approved = $scorePercent >= (int) $quiz->minimum_score;

            $attempt->update([
                'correct_answers' => $correctAnswers,
                'score_percent' => $scorePercent,
                'status' => $approved ? 'approved' : 'rejected',
            ]);

            return [
                'approved' => $approved,
                'score_percent' => $scorePercent,
            ];
        });

        return redirect()->route('tenant.student.quizzes.show', [
            'tenant' => $this->tenantRouteKey(),
            'enrollment' => $enrollment->id,
            'quiz' => $quiz->id,
        ])->with(
            $result['approved'] ? 'success' : 'status',
            $result['approved']
                ? 'Quiz aprobado con ' . $result['score_percent'] . '%. Módulo desbloqueado.'
                : 'Quiz no aprobado (' . $result['score_percent'] . '%). Intenta nuevamente.'
        );
    }

    public function markNotificationRead(Request $request, string $notificationId): RedirectResponse
    {
        $guard = $this->resolveStudentContext($request);

        if ($guard instanceof RedirectResponse) {
            return $guard;
        }

        $notification = DatabaseNotification::query()
            ->where('id', $notificationId)
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $guard->id)
            ->first();

        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return redirect()->route('tenant.student.courses.index', [
            'tenant' => $this->tenantRouteKey(),
        ]);
    }

    private function resolveStudentContext(Request $request): User|RedirectResponse
    {
        $tenantId = $this->tenantId();
        $tenantRouteKey = $this->tenantRouteKey();
        $sessionTenantId = (string) $request->session()->get('tenant_student_tenant_id', '');
        $studentId = (int) $request->session()->get('tenant_student_user_id', 0);

        if ($studentId <= 0 || $sessionTenantId !== $tenantId) {
            return redirect()->route('tenant.student.login', [
                'tenant' => $tenantRouteKey,
            ])->with('status', 'Inicia sesión como estudiante para continuar.');
        }

        $student = User::query()->find($studentId);

        if (! $student || ! $student->hasRole('student')) {
            $request->session()->forget([
                'tenant_student_user_id',
                'tenant_student_tenant_id',
            ]);

            return redirect()->route('tenant.student.login', [
                'tenant' => $tenantRouteKey,
            ])->withErrors([
                'email' => 'Tu sesión de estudiante ya no es válida.',
            ]);
        }

        return $student;
    }

    private function flattenLessons(CourseEnrollment $enrollment): Collection
    {
        return $enrollment->course->modules
            ->flatMap(fn ($module) => $module->lessons)
            ->values();
    }

    private function resolveSelectedLesson(Request $request, Collection $lessons, array $completedLessonIds, array $lockedLessonIds): ?CourseLesson
    {
        if ($lessons->isEmpty()) {
            return null;
        }

        $requestedLessonId = (int) $request->query('lesson', 0);
        $requestedLesson = $lessons->firstWhere('id', $requestedLessonId);

        if ($requestedLesson && ! in_array((int) $requestedLesson->id, $lockedLessonIds, true)) {
            return $requestedLesson;
        }

        $firstPending = $lessons->first(fn ($lesson) => ! in_array((int) $lesson->id, $completedLessonIds, true) && ! in_array((int) $lesson->id, $lockedLessonIds, true));

        if ($firstPending) {
            return $firstPending;
        }

        return $lessons->first(fn ($lesson) => ! in_array((int) $lesson->id, $lockedLessonIds, true)) ?: null;
    }

    private function calculateProgress(int $totalLessons, int $completedLessons): array
    {
        if ($totalLessons <= 0) {
            return [0, false];
        }

        $percent = (int) round(($completedLessons / $totalLessons) * 100);
        $isCompleted = $completedLessons >= $totalLessons;

        return [$percent, $isCompleted];
    }

    private function buildEmbedUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $trimmed = trim($url);

        if (preg_match('/youtube\.com\/watch\?v=([A-Za-z0-9_-]+)/', $trimmed, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        if (preg_match('/youtu\.be\/([A-Za-z0-9_-]+)/', $trimmed, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        if (preg_match('/vimeo\.com\/(\d+)/', $trimmed, $matches)) {
            return 'https://player.vimeo.com/video/' . $matches[1];
        }

        return $trimmed;
    }

    private function tenantId(): string
    {
        return (string) tenant('id');
    }

    private function tenantRouteKey(): string
    {
        return (string) (tenant('alias') ?: tenant('id'));
    }

    private function buildModuleUnlockMap(CourseEnrollment $enrollment): array
    {
        $modules = $enrollment->course->modules->sortBy('position')->values();

        $approvedQuizIds = QuizAttempt::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('status', 'approved')
            ->pluck('quiz_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $unlockMap = [];
        $completedLessonIds = EnrollmentLessonProgress::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereNotNull('completed_at')
            ->pluck('lesson_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($modules as $module) {
            $moduleId = (int) $module->id;
            $prerequisiteId = (int) ($module->prerequisite_module_id ?? 0);
            $isMandatory = (bool) ($module->is_prerequisite_mandatory ?? false);

            if ($prerequisiteId <= 0 || ! $isMandatory) {
                $unlockMap[$moduleId] = true;
                continue;
            }

            $prerequisiteModule = $modules->firstWhere('id', $prerequisiteId);

            if (! $prerequisiteModule) {
                $unlockMap[$moduleId] = true;
                continue;
            }

            $prerequisiteUnlocked = $unlockMap[$prerequisiteId] ?? false;
            $prerequisiteQuiz = $prerequisiteModule->quiz;

            $prerequisiteSatisfied = false;
            if ($prerequisiteQuiz && $prerequisiteQuiz->is_active) {
                $prerequisiteSatisfied = in_array((int) $prerequisiteQuiz->id, $approvedQuizIds, true);
            } else {
                $prerequisiteLessonIds = $prerequisiteModule->lessons
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $prerequisiteSatisfied = ! empty($prerequisiteLessonIds)
                    && count(array_diff($prerequisiteLessonIds, $completedLessonIds)) === 0;
            }

            $unlockMap[$moduleId] = $prerequisiteUnlocked && $prerequisiteSatisfied;
        }

        return $unlockMap;
    }

    private function isTenantSuspended(): bool
    {
        return (string) (tenant('status') ?? 'active') === 'suspended';
    }

    private function registerStreakReminderIfNeeded(User $student, int $streakDays): void
    {
        if ($streakDays < 2) {
            return;
        }

        $alreadySentToday = $student->notifications()
            ->where('type', StreakReminderNotification::class)
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if ($alreadySentToday) {
            return;
        }

        $student->notify(new StreakReminderNotification($streakDays));
    }
}
