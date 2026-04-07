<?php

namespace ArcheeNic\PermissionRegistry\Listeners;

use ArcheeNic\PermissionRegistry\Events\AfterPermissionGranted;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Notifications\PermissionGrantedNotification;
use Illuminate\Support\Facades\Log;

class SendPermissionGrantedNotification
{
    public function handle(AfterPermissionGranted $event): void
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
            $user->notify(new PermissionGrantedNotification(
                $event->permissionName,
                $event->service,
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to send PermissionGrantedNotification', [
                'user_id' => $user->id,
                'permission_name' => $event->permissionName,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
