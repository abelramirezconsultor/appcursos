<?php

namespace App\Services\Gamification;

use App\Models\Badge;
use App\Models\User;
use App\Models\UserGamificationStat;
use Illuminate\Support\Carbon;

class GamificationService
{
    public function registerLessonCompletion(User $user, int $lessonXpReward): array
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

        return [
            'xp_gain' => $xpGain,
            'xp_total' => $xpTotal,
            'level' => $level,
            'streak_days' => $streakDays,
            'new_badges' => $newBadges,
        ];
    }

    private function calculateLevel(int $xpTotal): int
    {
        return max(1, (int) floor($xpTotal / 100) + 1);
    }
}
