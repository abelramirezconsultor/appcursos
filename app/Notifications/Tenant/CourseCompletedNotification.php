<?php

namespace App\Notifications\Tenant;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CourseCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $courseTitle,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'course_completed',
            'title' => '¡Curso completado!',
            'message' => 'Completaste el curso "' . $this->courseTitle . '". ¡Excelente trabajo!',
        ];
    }
}
