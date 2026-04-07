<?php

namespace ArcheeNic\PermissionRegistry\Listeners;

use ArcheeNic\PermissionRegistry\Events\AfterPermissionRevoked;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Notifications\PermissionRevokedNotification;
use Illuminate\Support\Facades\Log;

class SendPermissionRevokedNotification
{
    public function handle(AfterPermissionRevoked $event): void
    {
        $userModel = config('permission-registry.user_model');
        if (!$userModel) {
            return;
        }

        $virtualUser = VirtualUser::find($event->userId);
        if (!$virtualUser || !$virtualUser->user_id) {
            return;
        }

        $user = $userModel::find($virtualUser->user_id);
        if (!$user) {
            return;
        }

        try {
            $user->notify(new PermissionRevokedNotification(
                $event->permissionName,
                $event->service,
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to send PermissionRevokedNotification', [
                'user_id' => $user->id,
                'permission_name' => $event->permissionName,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
