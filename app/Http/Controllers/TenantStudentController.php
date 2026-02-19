<?php

namespace App\Http\Controllers;

use App\Models\CourseLesson;
use App\Models\CourseEnrollment;
use App\Models\CourseProgress;
use App\Models\ActivationCode;
use App\Models\EnrollmentLessonProgress;
use App\Models\UserGamificationStat;
use App\Models\User;
use App\Services\Gamification\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TenantStudentController extends Controller
{
    public function index(Request $request): View|RedirectResponse
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

        return view('tenant.my-courses', [
            'student' => $student,
            'enrollments' => $enrollments,
            'assignedCodes' => $assignedCodes,
            'gamificationStat' => UserGamificationStat::query()->where('user_id', $student->id)->first(),
            'badges' => $student->badges()->orderBy('name')->get(['badges.id', 'badges.name']),
            'tenantRouteKey' => $tenantRouteKey,
        ]);
    }

    public function show(Request $request, CourseEnrollment $enrollment): View|RedirectResponse
    {
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
            'course.modules.lessons' => fn ($query) => $query->orderBy('position'),
            'progress',
            'lessonProgress:enrollment_id,lesson_id,progress_percent,viewed_at,completed_at',
        ]);

        $lessons = $this->flattenLessons($enrollment);
        $completedLessonIds = $enrollment->lessonProgress
            ->filter(fn ($progress) => ! is_null($progress->completed_at))
            ->pluck('lesson_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $selectedLesson = $this->resolveSelectedLesson($request, $lessons, $completedLessonIds);

        [$progressPercent, $isCompleted] = $this->calculateProgress($lessons->count(), count($completedLessonIds));

        return view('tenant.course-detail', [
            'tenantRouteKey' => $this->tenantRouteKey(),
            'student' => $student,
            'enrollment' => $enrollment,
            'lessons' => $lessons,
            'completedLessonIds' => $completedLessonIds,
            'selectedLesson' => $selectedLesson,
            'selectedVideoEmbedUrl' => $this->buildEmbedUrl($selectedLesson?->video_url),
            'progressPercent' => $progressPercent,
            'isCompleted' => $isCompleted,
            'gamificationStat' => UserGamificationStat::query()->where('user_id', $student->id)->first(),
            'badges' => $student->badges()->orderBy('name')->get(['badges.id', 'badges.name']),
        ]);
    }

    public function completeLesson(Request $request, CourseEnrollment $enrollment, CourseLesson $lesson): RedirectResponse
    {
        $guard = $this->resolveStudentContext($request);

        if ($guard instanceof RedirectResponse) {
            return $guard;
        }

        if ((int) $enrollment->user_id !== (int) $guard->id) {
            abort(403);
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
                    'new_badges' => [],
                ];
            }

            return app(GamificationService::class)->registerLessonCompletion(
                $guard,
                (int) ($lesson->xp_reward ?? 0)
            );
        });

        $message = 'Lección marcada como completada.';
        if (($result['xp_gain'] ?? 0) > 0) {
            $message .= ' +' . $result['xp_gain'] . ' XP.';
        }
        if (! empty($result['new_badges'])) {
            $message .= ' Insignias desbloqueadas: ' . implode(', ', $result['new_badges']) . '.';
        }

        return redirect()->route('tenant.student.courses.show', [
            'tenant' => $this->tenantRouteKey(),
            'enrollment' => $enrollment->id,
            'lesson' => $lesson->id,
        ])->with('success', $message);
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

    private function resolveSelectedLesson(Request $request, Collection $lessons, array $completedLessonIds): ?CourseLesson
    {
        if ($lessons->isEmpty()) {
            return null;
        }

        $requestedLessonId = (int) $request->query('lesson', 0);
        $requestedLesson = $lessons->firstWhere('id', $requestedLessonId);

        if ($requestedLesson) {
            return $requestedLesson;
        }

        $firstPending = $lessons->first(fn ($lesson) => ! in_array((int) $lesson->id, $completedLessonIds, true));

        return $firstPending ?: $lessons->first();
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
}
