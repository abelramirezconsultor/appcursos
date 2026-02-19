<?php

namespace App\Http\Controllers;

use App\Models\ActivationCode;
use App\Models\ActivationCodeRedemption;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\CourseProgress;
use App\Models\EnrollmentLessonProgress;
use App\Models\UserGamificationStat;
use App\Models\UserBadge;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantAdminController extends Controller
{
    public function show(Request $request, string $tenantId): View
    {
        $tenant = $this->resolveOwnedTenant($request, $tenantId);

        $snapshot = $tenant->run(function () {
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
                ->get(['id', 'course_id', 'title', 'position']);

            $recentRedemptions = ActivationCodeRedemption::query()
                ->with(['activationCode:id,code,course_id', 'activationCode.course:id,title', 'user:id,email'])
                ->orderByDesc('redeemed_at')
                ->limit(10)
                ->get(['id', 'activation_code_id', 'user_id', 'redeemed_at']);

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

            $badgesAwarded = UserBadge::query()->count();

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
                    'title' => $module->title,
                    'position' => $module->position,
                    'course_title' => $module->course?->title,
                ])->toArray(),
                'codes' => $codes->toArray(),
                'recent_redemptions' => $recentRedemptions->toArray(),
                'leaderboard' => $leaderboard,
                'summary' => [
                    'students_count' => $students->count(),
                    'courses_count' => $courses->count(),
                    'modules_count' => CourseModule::query()->count(),
                    'lessons_count' => CourseLesson::query()->count(),
                    'enrollments_count' => $enrollments->count(),
                    'lesson_progress_events' => EnrollmentLessonProgress::query()->count(),
                    'avg_progress_percent' => $avgProgress,
                    'completed_enrollments' => $completedEnrollments,
                    'active_codes' => ActivationCode::query()->where('is_active', true)->count(),
                    'badges_awarded' => $badgesAwarded,
                ],
            ];
        });

        return view('tenant-admin.show', [
            'tenant' => $tenant,
            'students' => $snapshot['students'],
            'courses' => $snapshot['courses'],
            'courseMetrics' => $snapshot['course_metrics'],
            'modules' => $snapshot['modules'],
            'codes' => $snapshot['codes'],
            'recentRedemptions' => $snapshot['recent_redemptions'],
            'leaderboard' => $snapshot['leaderboard'],
            'summary' => $snapshot['summary'],
        ]);
    }

    public function storeStudent(Request $request, string $tenantId): RedirectResponse
    {
        $tenant = $this->resolveOwnedTenant($request, $tenantId);

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
        });

        return back()->with('success', 'Usuario normal creado/asignado correctamente.');
    }

    public function storeCourse(Request $request, string $tenantId): RedirectResponse
    {
        $tenant = $this->resolveOwnedTenant($request, $tenantId);

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

    public function storeActivationCode(Request $request, string $tenantId): RedirectResponse
    {
        $tenant = $this->resolveOwnedTenant($request, $tenantId);

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

            ActivationCode::query()->create([
                'course_id' => $validated['course_id'],
                'assigned_user_id' => $validated['assigned_user_id'],
                'code' => $codeValue,
                'expires_at' => $validated['expires_at'] ?? null,
                'max_uses' => 1,
                'uses_count' => 0,
                'is_active' => true,
                'created_by' => $creatorId,
            ]);
        });

        return back()->with('success', 'Código de activación asignado correctamente.');
    }

    public function toggleActivationCode(Request $request, string $tenantId, int $codeId): RedirectResponse
    {
        $tenant = $this->resolveOwnedTenant($request, $tenantId);

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

        $validated = $request->validate([
            'course_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:1'],
        ]);

        $tenant->run(function () use ($validated): void {
            if (! Course::query()->whereKey($validated['course_id'])->exists()) {
                return;
            }

            $position = $validated['position'] ?? ((int) CourseModule::query()->where('course_id', $validated['course_id'])->max('position') + 1);

            CourseModule::query()->create([
                'course_id' => $validated['course_id'],
                'title' => $validated['title'],
                'position' => $position,
            ]);
        });

        return back()->with('success', 'Módulo creado correctamente.');
    }

    public function storeLesson(Request $request, string $tenantId): RedirectResponse
    {
        $tenant = $this->resolveOwnedTenant($request, $tenantId);

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
}
