<?php

namespace App\Services\Gamification;

use App\Models\Badge;
use App\Models\Course;
use App\Models\User;
use App\Models\UserCourseAchievement;
use App\Models\UserGamificationStat;
use App\Models\UserWeeklyGamificationStat;
use Illuminate\Support\Carbon;

class GamificationService
{
    public function registerLessonCompletion(
        User $user,
        int $lessonXpReward,
        ?int $courseId = null,
        ?int $courseProgressPercent = null,
        ?int $completedLessonsInCourse = null,
        ?int $totalLessonsInCourse = null
    ): array
    {
        $stat = UserGamificationStat::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'xp_total' => 0,
                'level' => 1,
                'streak_days' => 0,
                'last_activity_date' => null,
                'lessons_completed' => 0,
            ]
        );

        $xpGain = max(5, $lessonXpReward);
        $today = Carbon::today();
        $lastActivity = $stat->last_activity_date ? Carbon::parse($stat->last_activity_date) : null;

        $streakDays = $stat->streak_days;
        if (! $lastActivity) {
            $streakDays = 1;
        } elseif ($lastActivity->equalTo($today)) {
            $streakDays = max(1, $streakDays);
        } elseif ($lastActivity->copy()->addDay()->equalTo($today)) {
            $streakDays++;
        } else {
            $streakDays = 1;
        }

        $xpTotal = $stat->xp_total + $xpGain;
        $level = $this->calculateLevel($xpTotal);
        $lessonsCompleted = $stat->lessons_completed + 1;

        $stat->update([
            'xp_total' => $xpTotal,
            'level' => $level,
            'streak_days' => $streakDays,
            'last_activity_date' => $today,
            'lessons_completed' => $lessonsCompleted,
        ]);

        $this->registerWeeklyProgress($user, $today, $xpGain);

        $newBadges = [];
        $badges = Badge::query()->get();

        foreach ($badges as $badge) {
            if ($user->badges()->where('badges.id', $badge->id)->exists()) {
                continue;
            }

            $eligible = $xpTotal >= $badge->xp_required
                && $streakDays >= $badge->streak_required
                && $lessonsCompleted >= $badge->lessons_required;

            if ($eligible) {
                $user->badges()->attach($badge->id, [
                    'awarded_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $newBadges[] = $badge->name;
            }
        }

        $courseAchievements = [];
        if ($courseId) {
            $courseAchievements = $this->awardCourseAchievements(
                $user,
                $courseId,
                (int) ($courseProgressPercent ?? 0),
                (int) ($completedLessonsInCourse ?? 0),
                (int) ($totalLessonsInCourse ?? 0)
            );
        }

        return [
            'xp_gain' => $xpGain,
            'xp_total' => $xpTotal,
            'level' => $level,
            'streak_days' => $streakDays,
            'new_badges' => $newBadges,
            'new_course_achievements' => $courseAchievements,
        ];
    }

    private function registerWeeklyProgress(User $user, Carbon $today, int $xpGain): void
    {
        $weekStart = $today->copy()->startOfWeek();

        $weeklyStat = UserWeeklyGamificationStat::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'week_start' => $weekStart->toDateString(),
            ],
            [
                'xp_earned' => 0,
                'lessons_completed' => 0,
            ]
        );

        $weeklyStat->update([
            'xp_earned' => (int) $weeklyStat->xp_earned + $xpGain,
            'lessons_completed' => (int) $weeklyStat->lessons_completed + 1,
        ]);
    }

    private function awardCourseAchievements(User $user, int $courseId, int $courseProgressPercent, int $completedLessonsInCourse, int $totalLessonsInCourse): array
    {
        $course = Course::query()->find($courseId);
        if (! $course) {
            return [];
        }

        $definitions = [
            [
                'slug' => 'course_first_lesson',
                'name' => 'Primer avance en curso',
                'eligible' => $completedLessonsInCourse >= 1,
            ],
            [
                'slug' => 'course_halfway',
                'name' => 'Mitad del curso',
                'eligible' => $courseProgressPercent >= 50,
            ],
            [
                'slug' => 'course_completed',
                'name' => 'Curso completado',
                'eligible' => $totalLessonsInCourse > 0 && $completedLessonsInCourse >= $totalLessonsInCourse,
            ],
        ];

        $newAchievements = [];

        foreach ($definitions as $definition) {
            if (! $definition['eligible']) {
                continue;
            }

            $exists = UserCourseAchievement::query()
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->where('slug', $definition['slug'])
                ->exists();

            if ($exists) {
                continue;
            }

            UserCourseAchievement::query()->create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'slug' => $definition['slug'],
                'name' => $definition['name'],
                'awarded_at' => now(),
            ]);

            $newAchievements[] = $definition['name'] . ' · ' . $course->title;
        }

        return $newAchievements;
    }

    private function calculateLevel(int $xpTotal): int
    {
        return max(1, (int) floor($xpTotal / 100) + 1);
    }
}
