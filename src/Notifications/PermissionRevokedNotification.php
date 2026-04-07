<?php

namespace ArcheeNic\PermissionRegistry\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PermissionRevokedNotification extends Notification
{
    use Queueable;

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function __construct(
        private readonly string $permissionName,
        private readonly string $service,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'permission_revoked',
            'permission_name' => $this->permissionName,
            'service' => $this->service,
        ];
    }
}
