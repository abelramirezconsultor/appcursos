<?php

namespace App\Notifications\Tenant;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StudentWelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $tenantName,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'welcome',
            'title' => '¡Bienvenido a la plataforma!',
            'message' => 'Tu acceso como estudiante fue creado en ' . $this->tenantName . '.',
        ];
    }
}
