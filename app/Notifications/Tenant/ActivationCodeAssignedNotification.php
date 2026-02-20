<?php

namespace App\Notifications\Tenant;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ActivationCodeAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $courseTitle,
        private readonly string $code,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'activation_code',
            'title' => 'Tienes un código asignado',
            'message' => 'Se te asignó un código para el curso "' . $this->courseTitle . '": ' . $this->code,
        ];
    }
}
