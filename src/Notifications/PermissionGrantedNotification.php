<?php

namespace ArcheeNic\PermissionRegistry\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PermissionGrantedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $permissionName,
        private readonly string $service,
    ) {
    }

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'permission_granted',
            'permission_name' => $this->permissionName,
            'service' => $this->service,
        ];
    }
}
