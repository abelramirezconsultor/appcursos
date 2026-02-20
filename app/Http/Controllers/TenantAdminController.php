<?php

namespace App\Http\Controllers;

use App\Models\ActivationCode;
use App\Models\ActivationCodeAttempt;
use App\Models\ActivationCodeRedemption;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\CourseProgress;
use App\Models\EnrollmentLessonProgress;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\QuizAttempt;
use App\Models\UserWeeklyGamificationStat;
use App\Models\UserGamificationStat;
use App\Models\UserBadge;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Tenant\ActivationCodeAssignedNotification;
use App\Notifications\Tenant\StudentWelcomeNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantAdminController extends Controller
{
    public function show(Request $request, string $tenantId): View|RedirectResponse
    {
        $tenant = $this->resolveOwnedTenant($request, $tenantId);

        if ($this->isTenantSuspended($tenant)) {
            return redirect()->route('dashboard')->with('error', 'Este tenant está suspendido.');
        }

        $tenantTimezone = (string) ($tenant->timezone ?? 'America/Bogota');
        if (! in_array($tenantTimezone, timezone_identifiers_list(), true)) {
            $tenantTimezone = 'America/Bogota';
        }

        $preset = (string) $request->query('preset', 'current_week');
        $allowedPresets = ['current_week', 'last_7_days', 'last_30_days', 'this_month', 'custom'];
        if (! in_array($preset, $allowedPresets, true)) {
            $preset = 'current_week';
        }

        $requestedStartDate = (string) $request->query('start_date', '');
        $requestedEndDate = (string) $request->query('end_date', '');

        $today = Carbon::now($tenantTimezone)->startOfDay();
        $rangeStart = $today->copy()->startOfWeek();
        $rangeEnd = $today->copy()->endOfWeek();

        if ($preset === 'last_7_days') {
            $rangeStart = $today->copy()->subDays(6)->startOfDay();
            $rangeEnd = $today->copy()->endOfDay();
        } elseif ($preset === 'last_30_days') {
            $rangeStart = $today->copy()->subDays(29)->startOfDay();
            $rangeEnd = $today->copy()->endOfDay();
        } elseif ($preset === 'this_month') {
            $rangeStart = $today->copy()->startOfMonth();
            $rangeEnd = $today->copy()->endOfMonth();
        } elseif ($preset === 'custom') {
            if ($requestedStartDate !== '') {
                try {
                    $rangeStart = Carbon::parse($requestedStartDate, $tenantTimezone)->startOfDay();
                } catch (\Throwable) {
                    $rangeStart = $today->copy()->startOfWeek();
                }
            }

            if ($requestedEndDate !== '') {
                try {
                    $rangeEnd = Carbon::parse($requestedEndDate, $tenantTimezone)->endOfDay();
                } catch (\Throwable) {
                    $rangeEnd = $today->copy()->endOfWeek();
                }
            }
        }

        if ($rangeStart->greaterThan($rangeEnd)) {
            [$rangeStart, $rangeEnd] = [$rangeEnd->copy()->startOfDay(), $rangeStart->copy()->endOfDay()];
        }

        $queryUtcStart = $rangeStart->copy()->setTimezone('UTC');
        $queryUtcEnd = $rangeEnd->copy()->setTimezone('UTC');

        $snapshot = $tenant->run(function () use ($queryUtcStart, $queryUtcEnd) {
            $students = User::query()
                ->whereHas('roles', fn ($query) => $query->where('name', 'student'))
                ->orderByDesc('id')
                ->get(['id', 'name', 'email']);

            $courses = Course::query()
                ->with(['modules.lessons'])
                ->orderByDesc('id')
                ->get(['id', 'title', 'status']);

            $enrollments = CourseEnrollment::query()
                ->with('progress:id,enrollment_id,progress_percent,completed_at')
                ->get(['id', 'user_id', 'course_id', 'status']);

            $avgProgress = (int) round((float) CourseProgress::query()->avg('progress_percent'));
            $completedEnrollments = CourseProgress::query()->whereNotNull('completed_at')->count();

            $courseMetrics = $courses->map(function (Course $course) use ($enrollments): array {
                $courseEnrollments = $enrollments->where('course_id', $course->id);
                $enrollmentCount = $courseEnrollments->count();
                $courseAvgProgress = (int) round((float) $courseEnrollments
                    ->pluck('progress.progress_percent')
                    ->filter(fn ($value) => ! is_null($value))
                    ->avg());

                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'status' => $course->status,
                    'modules_count' => $course->modules->count(),
                    'lessons_count' => $course->modules->flatMap(fn ($module) => $module->lessons)->count(),
                    'enrollments_count' => $enrollmentCount,
                    'avg_progress_percent' => $enrollmentCount > 0 ? $courseAvgProgress : 0,
                ];
            })->values();

            $codes = ActivationCode::query()
                ->with(['course:id,title', 'assignedUser:id,email'])
                ->orderByDesc('id')
                ->limit(20)
                ->get(['id', 'course_id', 'assigned_user_id', 'code', 'is_active', 'expires_at', 'uses_count', 'max_uses']);

            $modules = CourseModule::query()
                ->with('course:id,title')
                ->orderBy('course_id')
                ->orderBy('position')
                ->get(['id', 'course_id', 'title', 'position', 'prerequisite_module_id', 'is_prerequisite_mandatory']);

            $quizzes = Quiz::query()
                ->with(['course:id,title', 'module:id,title', 'questions:id,quiz_id'])
                ->orderByDesc('id')
                ->get(['id', 'course_id', 'module_id', 'title', 'minimum_score', 'max_attempts', 'is_active']);

            $recentRedemptions = ActivationCodeRedemption::query()
                ->with(['activationCode:id,code,course_id', 'activationCode.course:id,title', 'user:id,email'])
                ->orderByDesc('redeemed_at')
                ->limit(10)
                ->get(['id', 'activation_code_id', 'user_id', 'redeemed_at']);

            $recentAttempts = ActivationCodeAttempt::query()
                ->with(['activationCode:id,code', 'user:id,email'])
                ->orderByDesc('attempted_at')
                ->limit(10)
                ->get(['id', 'activation_code_id', 'user_id', 'code_input', 'email', 'status', 'reason', 'ip_address', 'attempted_at']);

            $leaderboard = UserGamificationStat::query()
                ->with('user:id,name,email')
                ->orderByDesc('xp_total')
                ->orderByDesc('streak_days')
                ->limit(10)
                ->get(['id', 'user_id', 'xp_total', 'level', 'streak_days', 'lessons_completed'])
                ->map(fn (UserGamificationStat $stat) => [
                    'name' => $stat->user?->name,
                    'email' => $stat->user?->email,
                    'xp_total' => $stat->xp_total,
                    'level' => $stat->level,
                    'streak_days' => $stat->streak_days,
                    'lessons_completed' => $stat->lessons_completed,
                ])
                ->toArray();

            $weeklyLeaderboard = DB::table('enrollment_lesson_progress as elp')
                ->join('course_enrollments as ce', 'ce.id', '=', 'elp.enrollment_id')
                ->join('users as u', 'u.id', '=', 'ce.user_id')
                ->leftJoin('course_lessons as cl', 'cl.id', '=', 'elp.lesson_id')
                ->whereNotNull('elp.completed_at')
                ->whereBetween('elp.completed_at', [$queryUtcStart->toDateTimeString(), $queryUtcEnd->toDateTimeString()])
                ->select([
                    'ce.user_id',
                    'u.name',
                    'u.email',
                    DB::raw('COUNT(*) as lessons_completed_total'),
                    DB::raw('SUM(GREATEST(5, COALESCE(cl.xp_reward, 0))) as xp_earned_total'),
                ])
                ->groupBy('ce.user_id', 'u.name', 'u.email')
                ->orderByDesc('xp_earned_total')
                ->orderByDesc('lessons_completed_total')
                ->limit(10)
                ->get()
                ->map(fn ($stat) => [
                    'name' => $stat->name,
                    'email' => $stat->email,
                    'xp_earned' => (int) $stat->xp_earned_total,
                    'lessons_completed' => (int) $stat->lessons_completed_total,
                ])
                ->toArray();

            $badgesAwarded = UserBadge::query()->count();

            $allCodes = ActivationCode::query()->get(['id', 'course_id', 'uses_count']);
            $codesTotal = $allCodes->count();
            $codesUsed = $allCodes->where('uses_count', '>', 0)->count();
            $codesUnused = max(0, $codesTotal - $codesUsed);
            $codeConversionPercent = $codesTotal > 0 ? (int) round(($codesUsed / $codesTotal) * 100) : 0;

            $codeConversionByCourse = Course::query()
                ->withCount([
                    'activationCodes as codes_total_count',
                    'activationCodes as codes_used_count' => fn ($query) => $query->where('uses_count', '>', 0),
                ])
                ->orderByDesc('codes_total_count')
                ->get(['id', 'title'])
                ->map(fn (Course $course) => [
                    'course_title' => $course->title,
                    'codes_total' => (int) $course->codes_total_count,
                    'codes_used' => (int) $course->codes_used_count,
                    'codes_unused' => max(0, (int) $course->codes_total_count - (int) $course->codes_used_count),
                    'conversion_percent' => (int) $course->codes_total_count > 0
                        ? (int) round(((int) $course->codes_used_count / (int) $course->codes_total_count) * 100)
                        : 0,
                ])
                ->values()
                ->toArray();

            return [
                'students' => $students->toArray(),
                'courses' => $courses->map(fn (Course $course) => [
                    'id' => $course->id,
                    'title' => $course->title,
                    'status' => $course->status,
                ])->toArray(),
                'course_metrics' => $courseMetrics->toArray(),
                'modules' => $modules->map(fn (CourseModule $module) => [
                    'id' => $module->id,
                    'course_id' => $module->course_id,
                    'title' => $module->title,
                    'position' => $module->position,
                    'prerequisite_module_id' => $module->prerequisite_module_id,
                    'is_prerequisite_mandatory' => (bool) $module->is_prerequisite_mandatory,
                    'course_title' => $module->course?->title,
                ])->toArray(),
                'quizzes' => $quizzes->map(fn (Quiz $quiz) => [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'course_title' => $quiz->course?->title,
                    'module_title' => $quiz->module?->title,
                    'minimum_score' => $quiz->minimum_score,
                    'max_attempts' => $quiz->max_attempts,
                    'is_active' => $quiz->is_active,
                    'questions_count' => $quiz->questions->count(),
                ])->toArray(),
                'codes' => $codes->toArray(),
                'recent_redemptions' => $recentRedemptions->toArray(),
                'recent_attempts' => $recentAttempts->toArray(),
                'code_conversion_by_course' => $codeConversionByCourse,
                'leaderboard' => $leaderboard,
                'weekly_leaderboard' => $weeklyLeaderboard,
                'summary' => [
                    'students_count' => $students->count(),
                    'courses_count' => $courses->count(),
                    'modules_count' => CourseModule::query()->count(),
                    'lessons_count' => CourseLesson::query()->count(),
                    'enrollments_count' => $enrollments->count(),
                    'lesson_progress_events' => EnrollmentLessonProgress::query()->count(),
                    'quizzes_count' => Quiz::query()->count(),
                    'quiz_attempts_count' => QuizAttempt::query()->count(),
                    'avg_progress_percent' => $avgProgress,
                    'completed_enrollments' => $completedEnrollments,
                    'active_codes' => ActivationCode::query()->where('is_active', true)->count(),
                    'codes_total' => $codesTotal,
                    'codes_used' => $codesUsed,
                    'codes_unused' => $codesUnused,
                    'code_conversion_percent' => $codeConversionPercent,
                    'failed_code_attempts' => ActivationCodeAttempt::query()->where('status', 'failed')->count(),
                    'badges_awarded' => $badgesAwarded,
                ],
            ];
        });

        $usersLimit = (int) ($tenant->max_users ?? 0);
        $coursesLimit = (int) ($tenant->max_courses ?? 0);

        $studentsCount = (int) ($snapshot['summary']['students_count'] ?? 0);
        $coursesCount = (int) ($snapshot['summary']['courses_count'] ?? 0);

        $limits = [
            'plan_code' => (string) ($tenant->plan_code ?? 'starter'),
            'status' => (string) ($tenant->status ?? 'active'),
            'users' => [
                'used' => $studentsCount,
                'max' => $usersLimit,
                'percent' => $usersLimit > 0 ? (int) round(($studentsCount / $usersLimit) * 100) : 0,
            ],
            'courses' => [
                'used' => $coursesCount,
                'max' => $coursesLimit,
                'percent' => $coursesLimit > 0 ? (int) round(($coursesCount / $coursesLimit) * 100) : 0,
            ],
        ];

        return view('tenant-admin.show', [
            'tenant' => $tenant,
            'students' => $snapshot['students'],
            'courses' => $snapshot['courses'],
            'courseMetrics' => $snapshot['course_metrics'],
            'modules' => $snapshot['modules'],
            'quizzes' => $snapshot['quizzes'],
            'codes' => $snapshot['codes'],
            'recentRedemptions' => $snapshot['recent_redemptions'],
            'recentAttempts' => $snapshot['recent_attempts'],
            'codeConversionByCourse' => $snapshot['code_conversion_by_course'],
            'leaderboard' => $snapshot['leaderboard'],
            'weeklyLeaderboard' => $snapshot['weekly_leaderboard'],
            'weeklyPreset' => $preset,
            'weeklySelectedStartDate' => $rangeStart->format('Y-m-d'),
            'weeklySelectedEndDate' => $rangeEnd->format('Y-m-d'),
            'weeklyTimezone' => $tenantTimezone,
            'summary' => $snapshot['summary'],
            'limits' => $limits,
        ]);
    }

    public function exportReportsCsv(Request $request, string $tenantId): StreamedResponse|RedirectResponse
    {
        $tenant = $this->resolveOwnedTenant($request, $tenantId);

        if ($this->isTenantSuspended($tenant)) {
            return redirect()->route('dashboard')->with('error', 'Este tenant está suspendido.');
        }

        $rows = $tenant->run(function (): array {
            $courses = Course::query()
                ->with([
                    'modules.lessons:id,module_id',
                    'enrollments.progress:id,enrollment_id,progress_percent',
                ])
                ->withCount([
                    'activationCodes as codes_total_count',
                    'activationCodes as codes_used_count' => fn ($query) => $query->where('uses_count', '>', 0),
                ])
                ->orderBy('title')
                ->get(['id', 'title', 'status']);

            return $courses->map(function (Course $course): array {
                $enrollmentCount = $course->enrollments->count();

                $avgProgressPercent = (int) round((float) $course->enrollments
                    ->pluck('progress.progress_percent')
                    ->filter(fn ($value) => ! is_null($value))
                    ->avg());

                $codesTotal = (int) ($course->codes_total_count ?? 0);
                $codesUsed = (int) ($course->codes_used_count ?? 0);
                $codesUnused = max(0, $codesTotal - $codesUsed);

                return [
                    'course_title' => $course->title,
                    'status' => $course->status,
                    'modules_count' => (int) $course->modules->count(),
                    'lessons_count' => (int) $course->modules->flatMap(fn ($module) => $module->lessons)->count(),
                    'enrollments_count' => $enrollmentCount,
                    'avg_progress_percent' => $enrollmentCount > 0 ? $avgProgressPercent : 0,
                    'codes_total' => $codesTotal,
                    'codes_used' => $codesUsed,
                    'codes_unused' => $codesUnused,
                    'conversion_percent' => $codesTotal > 0
                        ? (int) round(($codesUsed / $codesTotal) * 100)
                        : 0,
                ];
            })->values()->all();
        });

        $filename = 'reportes_cursos_' . $tenant->id . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'wb');
            if (! $output) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'curso',
                'estado',
                'modulos',
                'lecciones',
                'matriculas',
                'avance_promedio_percent',
                'codigos_total',
                'codigos_usados',
                'codigos_no_usados',
                'conversion_percent',
            ]);

            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['course_title'],
                    $row['status'],
                    $row['modules_count'],
                    $row['lessons_count'],
                    $row['enrollments_count'],
                    $row['avg_progress_percent'],
                    $row['codes_total'],
                    $row['codes_used'],
                    $row['codes_unused'],
                    $row['conversion_percent'],
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function storeStudent(Request $request, string $tenantId): RedirectResponse
    {
        $tenant = $this->resolveOwnedTenant($request, $tenantId);

        if ($this->isTenantSuspended($tenant)) {
            return back()->with('error', 'No se pueden crear estudiantes: tenant suspendido.');
        }

        if ($this->studentsLimitReached($tenant)) {
            return back()->with('error', 'Límite de estudiantes alcanzado para el plan actual.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $tenant->run(function () use ($validated) {
            $student = User::query()->firstOrCreate(
                ['email' => $validated['email']],
                [
                    'name' => $validated['name'],
                    'password' => Hash::make($validated['password']),
                ]
            );

            if (! $student->hasRole('student')) {
                $student->assignRole('student');
            }

            $student->notify(new StudentWelcomeNotification((string) tenant('name')));
        });

        return back()->with('success', 'Usuario normal creado/asignado correctamente.');
    }

    public function storeCourse(Request $request, string $tenantId): RedirectResponse
    {
        $tenant = $this->resolveOwnedTenant($request, $tenantId);

        if ($this->isTenantSuspended($tenant)) {
            return back()->with('error', 'No se pueden crear cursos: tenant suspendido.');
        }

        if ($this->coursesLimitReached($tenant)) {
            return back()->with('error', 'Límite de cursos alcanzado para el plan actual.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'published'])],
        ]);

        $ownerEmail = $request->user()->email;

        $tenant->run(function () use ($validated, $ownerEmail) {
            $slug = Str::slug($validated['title']);
            $baseSlug = Str::limit($slug ?: 'curso', 80, '');
            $candidate = $baseSlug;
            $counter = 2;

            while (Course::query()->where('slug', $candidate)->exists()) {
                $candidate = Str::limit($baseSlug, 70, '') . '-' . $counter;
                $counter++;
            }

            $creatorId = User::query()->where('email', $ownerEmail)->value('id');

            Course::query()->create([
                'title' => $validated['title'],
                'slug' => $candidate,
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'],
                'created_by' => $creatorId,
            ]);
        });

        return back()->with('success', 'Curso creado correctamente.');
    }

    public function toggleCourseStatus(Request $request, string $tenantId, int $courseId): RedirectResponse
    {
        $tenant = $this->resolveOwnedTenant($request, $tenantId);

        if ($this->isTenantSuspended($tenant)) {
            return back()->with('error', 'No se pueden modificar cursos: tenant suspendido.');
        }

        $updated = false;

        $tenant->run(function () use ($courseId, &$updated): void {
            $course = Course::query()->find($courseId);

            if (! $course) {
                return;
            }

            $nextStatus = $course->status === 'published' ? 'draft' : 'published';
            $course->update(['status' => $nextStatus]);
            $updated = true;
        });

        if (! $updated) {
            return back()->with('error', 'Curso no encontrado.');
        }

        return back()->with('success', 'Estado del curso actualizado correctamente.');
    }

    public function destroyCourse(Request $request, string $tenantId, int $courseId): RedirectResponse
    {
        $tenant = $this->resolveOwnedTenant($request, $tenantId);

        if ($this->isTenantSuspended($tenant)) {
            return back()->with('error', 'No se pueden eliminar cursos: tenant suspendido.');
        }

        $deleted = false;

        $tenant->run(function () use ($courseId, &$deleted): void {
            $course = Course::query()->find($courseId);

            if (! $course) {
                return;
            }

            $course->delete();
            $deleted = true;
        });

        if (! $deleted) {
            return back()->with('error', 'Curso no encontrado.');
        }

        return back()->with('success', 'Curso eliminado correctamente.');
    }

    public function storeActivationCode(Request $request, string $tenantId): RedirectResponse
    {
        $tenant = $this->resolveOwnedTenant($request, $tenantId);

        if ($this->isTenantSuspended($tenant)) {
            return back()->with('error', 'No se pueden asignar códigos: tenant suspendido.');
        }

        $validated = $request->validate([
            'course_id' => ['required', 'integer'],
            'assigned_user_id' => ['required', 'integer'],
            'code' => ['nullable', 'string', 'max:32'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $ownerEmail = $request->user()->email;

        $tenant->run(function () use ($validated, $ownerEmail) {
            $courseExists = Course::query()->whereKey($validated['course_id'])->exists();
            $studentExists = User::query()->whereKey($validated['assigned_user_id'])->exists();

            if (! $courseExists || ! $studentExists) {
                return;
            }

            $codeValue = $validated['code'] ? Str::upper($validated['code']) : $this->generateUniqueCode();
            $creatorId = User::query()->where('email', $ownerEmail)->value('id');

            $activationCode = ActivationCode::query()->create([
                'course_id' => $validated['course_id'],
                'assigned_user_id' => $validated['assigned_user_id'],
                'code' => $codeValue,
                'expires_at' => $validated['expires_at'] ?? null,
                'max_uses' => 1,
                'uses_count' => 0,
                'is_active' => true,
                'created_by' => $creatorId,
            ]);

            $student = User::query()->find($validated['assigned_user_id']);
            $course = Course::query()->find($validated['course_id']);

            if ($student && $course) {
                $student->notify(new ActivationCodeAssignedNotification($course->title, $activationCode->code));
            }
        });

        return back()->with('success', 'Código de activación asignado correctamente.');
    }

    public function toggleActivationCode(Request $request, string $tenantId, int $codeId): RedirectResponse
    {
        $tenant = $this->resolveOwnedTenant($request, $tenantId);

        if ($this->isTenantSuspended($tenant)) {
            return back()->with('error', 'No se pueden modificar códigos: tenant suspendido.');
        }

        $tenant->run(function () use ($codeId): void {
            $code = ActivationCode::query()->find($codeId);

            if (! $code) {
                return;
            }

            $code->update([
                'is_active' => ! $code->is_active,
                'revoked_at' => $code->is_active ? now() : null,
            ]);
        });

        return back()->with('success', 'Estado del código actualizado correctamente.');
    }

    public function storeModule(Request $request, string $tenantId): RedirectResponse
    {
        $tenant = $this->resolveOwnedTenant($request, $tenantId);

        if ($this->isTenantSuspended($tenant)) {
            return back()->with('error', 'No se pueden crear módulos: tenant suspendido.');
        }

        $validated = $request->validate([
            'course_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:1'],
            'prerequisite_module_id' => ['nullable', 'integer'],
            'is_prerequisite_mandatory' => ['nullable', 'boolean'],
        ]);

        $tenant->run(function () use ($validated): void {
            $course = Course::query()->find($validated['course_id']);

            if (! $course) {
                return;
            }

            $prerequisiteModuleId = isset($validated['prerequisite_module_id'])
                ? (int) $validated['prerequisite_module_id']
                : null;

            if ($prerequisiteModuleId) {
                $prerequisiteExistsInCourse = CourseModule::query()
                    ->whereKey($prerequisiteModuleId)
                    ->where('course_id', $course->id)
                    ->exists();

                if (! $prerequisiteExistsInCourse) {
                    return;
                }
            }

            $position = $validated['position'] ?? ((int) CourseModule::query()->where('course_id', $validated['course_id'])->max('position') + 1);

            CourseModule::query()->create([
                'course_id' => $validated['course_id'],
                'title' => $validated['title'],
                'position' => $position,
                'prerequisite_module_id' => $prerequisiteModuleId,
                'is_prerequisite_mandatory' => $prerequisiteModuleId
                    ? (bool) ($validated['is_prerequisite_mandatory'] ?? false)
                    : false,
            ]);
        });

        return back()->with('success', 'Módulo creado correctamente.');
    }

    public function storeLesson(Request $request, string $tenantId): RedirectResponse
    {
        $tenant = $this->resolveOwnedTenant($request, $tenantId);

        if ($this->isTenantSuspended($tenant)) {
            return back()->with('error', 'No se pueden crear lecciones: tenant suspendido.');
        }

        $validated = $request->validate([
            'module_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'video_url' => ['nullable', 'url', 'max:1000'],
            'duration_seconds' => ['nullable', 'integer', 'min:30', 'max:900'],
            'position' => ['nullable', 'integer', 'min:1'],
            'xp_reward' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_preview' => ['nullable', 'boolean'],
        ]);

        $tenant->run(function () use ($validated): void {
            if (! CourseModule::query()->whereKey($validated['module_id'])->exists()) {
                return;
            }

            $position = $validated['position'] ?? ((int) CourseLesson::query()->where('module_id', $validated['module_id'])->max('position') + 1);

            CourseLesson::query()->create([
                'module_id' => $validated['module_id'],
                'title' => $validated['title'],
                'video_url' => $validated['video_url'] ?? null,
                'duration_seconds' => $validated['duration_seconds'] ?? null,
                'position' => $position,
                'xp_reward' => $validated['xp_reward'] ?? 0,
                'is_preview' => (bool) ($validated['is_preview'] ?? false),
            ]);
        });

        return back()->with('success', 'Lección creada correctamente.');
    }

    public function updateModulePrerequisite(Request $request, string $tenantId, int $moduleId): RedirectResponse
    {
        $tenant = $this->resolveOwnedTenant($request, $tenantId);

        if ($this->isTenantSuspended($tenant)) {
            return back()->with('error', 'No se pueden modificar módulos: tenant suspendido.');
        }

        $validated = $request->validate([
            'prerequisite_module_id' => ['nullable', 'integer'],
            'is_prerequisite_mandatory' => ['nullable', 'boolean'],
        ]);

        $result = $tenant->run(function () use ($moduleId, $validated): array {
            $module = CourseModule::query()->find($moduleId);

            if (! $module) {
                return [
                    'ok' => false,
                    'error' => 'No se encontró el módulo a actualizar.',
                ];
            }

            $prerequisiteModuleId = isset($validated['prerequisite_module_id'])
                ? (int) $validated['prerequisite_module_id']
                : null;

            if ($prerequisiteModuleId === $module->id) {
                return [
                    'ok' => false,
                    'error' => 'Un módulo no puede depender de sí mismo.',
                ];
            }

            if ($prerequisiteModuleId) {
                $prerequisiteExistsInCourse = CourseModule::query()
                    ->whereKey($prerequisiteModuleId)
                    ->where('course_id', $module->course_id)
                    ->exists();

                if (! $prerequisiteExistsInCourse) {
                    return [
                        'ok' => false,
                        'error' => 'El prerequisito debe pertenecer al mismo curso.',
                    ];
                }

                if ($this->hasPrerequisiteCycle($module->id, $prerequisiteModuleId)) {
                    return [
                        'ok' => false,
                        'error' => 'Esta relación crea un ciclo de prerequisitos.',
                    ];
                }
            }

            $module->update([
                'prerequisite_module_id' => $prerequisiteModuleId,
                'is_prerequisite_mandatory' => $prerequisiteModuleId
                    ? (bool) ($validated['is_prerequisite_mandatory'] ?? false)
                    : false,
            ]);

            return ['ok' => true];
        });

        if (! ($result['ok'] ?? false)) {
            return back()->with('error', (string) ($result['error'] ?? 'No se pudo actualizar el prerequisito.'));
        }

        return back()->with('success', 'Prerequisito del módulo actualizado correctamente.');
    }

    public function storeQuiz(Request $request, string $tenantId): RedirectResponse
    {
        $tenant = $this->resolveOwnedTenant($request, $tenantId);

        if ($this->isTenantSuspended($tenant)) {
            return back()->with('error', 'No se pueden crear evaluaciones: tenant suspendido.');
        }

        $validated = $request->validate([
            'course_id' => ['required', 'integer'],
            'module_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'minimum_score' => ['required', 'integer', 'min:0', 'max:100'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $tenant->run(function () use ($validated): void {
            $course = Course::query()->find($validated['course_id']);

            if (! $course) {
                return;
            }

            $moduleId = $validated['module_id'] ?? null;

            if ($moduleId) {
                $module = CourseModule::query()
                    ->whereKey($moduleId)
                    ->where('course_id', $course->id)
                    ->first();

                if (! $module) {
                    return;
                }
            }

            Quiz::query()->create([
                'course_id' => $course->id,
                'module_id' => $moduleId,
                'title' => $validated['title'],
                'minimum_score' => (int) $validated['minimum_score'],
                'max_attempts' => $validated['max_attempts'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);
        });

        return back()->with('success', 'Quiz creado correctamente.');
    }

    public function storeQuizQuestion(Request $request, string $tenantId): RedirectResponse
    {
        $tenant = $this->resolveOwnedTenant($request, $tenantId);

        if ($this->isTenantSuspended($tenant)) {
            return back()->with('error', 'No se pueden crear preguntas: tenant suspendido.');
        }

        $validated = $request->validate([
            'quiz_id' => ['required', 'integer'],
            'question_text' => ['required', 'string'],
            'points' => ['nullable', 'integer', 'min:1', 'max:100'],
            'position' => ['nullable', 'integer', 'min:1'],
            'option_a' => ['required', 'string', 'max:500'],
            'option_b' => ['required', 'string', 'max:500'],
            'option_c' => ['required', 'string', 'max:500'],
            'option_d' => ['required', 'string', 'max:500'],
            'correct_option' => ['required', Rule::in(['a', 'b', 'c', 'd'])],
        ]);

        $tenant->run(function () use ($validated): void {
            $quiz = Quiz::query()->find($validated['quiz_id']);

            if (! $quiz) {
                return;
            }

            $position = $validated['position'] ?? ((int) QuizQuestion::query()->where('quiz_id', $quiz->id)->max('position') + 1);

            $question = QuizQuestion::query()->create([
                'quiz_id' => $quiz->id,
                'question_text' => $validated['question_text'],
                'points' => (int) ($validated['points'] ?? 1),
                'position' => $position,
            ]);

            $options = [
                'a' => $validated['option_a'],
                'b' => $validated['option_b'],
                'c' => $validated['option_c'],
                'd' => $validated['option_d'],
            ];

            $index = 1;
            foreach ($options as $key => $text) {
                QuizOption::query()->create([
                    'question_id' => $question->id,
                    'option_text' => $text,
                    'is_correct' => $validated['correct_option'] === $key,
                    'position' => $index,
                ]);
                $index++;
            }
        });

        return back()->with('success', 'Pregunta de quiz creada correctamente.');
    }

    private function resolveOwnedTenant(Request $request, string $tenantId): Tenant
    {
        return Tenant::query()
            ->where('id', $tenantId)
            ->where('owner_email', $request->user()->email)
            ->firstOrFail();
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = Str::upper(Str::random(10));
        } while (ActivationCode::query()->where('code', $code)->exists());

        return $code;
    }

    private function hasPrerequisiteCycle(int $moduleId, int $candidatePrerequisiteId): bool
    {
        $currentModuleId = $candidatePrerequisiteId;
        $visited = [];

        while ($currentModuleId > 0) {
            if ($currentModuleId === $moduleId) {
                return true;
            }

            if (in_array($currentModuleId, $visited, true)) {
                return true;
            }

            $visited[] = $currentModuleId;

            $nextPrerequisiteId = (int) (CourseModule::query()
                ->whereKey($currentModuleId)
                ->value('prerequisite_module_id') ?? 0);

            if ($nextPrerequisiteId <= 0) {
                return false;
            }

            $currentModuleId = $nextPrerequisiteId;
        }

        return false;
    }

    private function isTenantSuspended(Tenant $tenant): bool
    {
        return (string) ($tenant->status ?? 'active') === 'suspended';
    }

    private function studentsLimitReached(Tenant $tenant): bool
    {
        $maxUsers = (int) ($tenant->max_users ?? 0);

        if ($maxUsers <= 0) {
            return false;
        }

        $studentsCount = $tenant->run(fn () => User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'student'))
            ->count());

        return $studentsCount >= $maxUsers;
    }

    private function coursesLimitReached(Tenant $tenant): bool
    {
        $maxCourses = (int) ($tenant->max_courses ?? 0);

        if ($maxCourses <= 0) {
            return false;
        }

        $coursesCount = $tenant->run(fn () => Course::query()->count());

        return $coursesCount >= $maxCourses;
    }
}
