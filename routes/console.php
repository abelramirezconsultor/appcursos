<?php

use App\Models\CourseLesson;
use App\Models\EnrollmentLessonProgress;
use App\Models\Tenant;
use App\Models\UserWeeklyGamificationStat;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('tenants:backfill-weekly-gamification {--tenant=} {--dry-run}', function () {
    $tenantId = (string) $this->option('tenant');
    $dryRun = (bool) $this->option('dry-run');

    $tenantsQuery = Tenant::query();
    if ($tenantId !== '') {
        $tenantsQuery->where('id', $tenantId);
    }

    $tenants = $tenantsQuery->get();

    if ($tenants->isEmpty()) {
        $this->warn('No se encontraron tenants para procesar.');
        return;
    }

    foreach ($tenants as $tenant) {
        /** @var Tenant $tenant */
        $this->line("Tenant: {$tenant->id}");

        $result = $tenant->run(function () use ($dryRun): array {
            $completedRows = EnrollmentLessonProgress::query()
                ->with([
                    'enrollment:id,user_id',
                    'lesson:id,xp_reward',
                ])
                ->whereNotNull('completed_at')
                ->get(['id', 'enrollment_id', 'lesson_id', 'completed_at']);

            $aggregated = [];

            foreach ($completedRows as $row) {
                $userId = (int) ($row->enrollment?->user_id ?? 0);
                if ($userId <= 0) {
                    continue;
                }

                $weekStart = Carbon::parse((string) $row->completed_at)->startOfWeek()->toDateString();
                $xpGain = max(5, (int) ($row->lesson?->xp_reward ?? 0));
                $key = $userId . '|' . $weekStart;

                if (! isset($aggregated[$key])) {
                    $aggregated[$key] = [
                        'user_id' => $userId,
                        'week_start' => $weekStart,
                        'xp_earned' => 0,
                        'lessons_completed' => 0,
                    ];
                }

                $aggregated[$key]['xp_earned'] += $xpGain;
                $aggregated[$key]['lessons_completed'] += 1;
            }

            if (! $dryRun) {
                UserWeeklyGamificationStat::query()->delete();

                foreach ($aggregated as $row) {
                    UserWeeklyGamificationStat::query()->create($row);
                }
            }

            return [
                'rows_source' => $completedRows->count(),
                'rows_target' => count($aggregated),
            ];
        });

        $prefix = $dryRun ? '[dry-run] ' : '';
        $this->info($prefix . 'Lecciones completadas: ' . $result['rows_source']);
        $this->info($prefix . 'Filas semanales calculadas: ' . $result['rows_target']);
    }
})->purpose('Reconstruye user_weekly_gamification_stats desde progreso histórico por tenant');
