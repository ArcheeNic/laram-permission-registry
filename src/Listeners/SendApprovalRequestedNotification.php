<?php

namespace ArcheeNic\PermissionRegistry\Listeners;

use ArcheeNic\PermissionRegistry\Events\ApprovalRequested;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Notifications\ApprovalRequestedNotification;
use Illuminate\Support\Facades\Log;

class SendApprovalRequestedNotification
{
    public function handle(ApprovalRequested $event): void
    {
        $userModel = config('permission-registry.user_model');
        if (!$userModel) {
            return;
        }

        $virtualUsers = VirtualUser::whereIn('id', $event->approverIds)
            ->whereNotNull('user_id')
            ->get();

        foreach ($virtualUsers as $virtualUser) {
            $user = $userModel::find($virtualUser->user_id);
            if (!$user) {
                continue;
            }

            try {
                $user->notify(new ApprovalRequestedNotification($event->approvalRequest));
            } catch (\Throwable $e) {
                Log::error('Failed to send ApprovalRequestedNotification', [
                    'user_id' => $user->id,
                    'approval_request_id' => $event->approvalRequest->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
