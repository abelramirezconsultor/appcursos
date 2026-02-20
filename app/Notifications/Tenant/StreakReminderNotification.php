<?php

namespace App\Notifications\Tenant;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StreakReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $streakDays,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'streak_reminder',
            'title' => 'Mantén tu racha',
            'message' => 'Vas en una racha de ' . $this->streakDays . ' días. ¡No la pierdas mañana!',
        ];
    }
}
