<?php

namespace ArcheeNic\PermissionRegistry\Listeners;

use ArcheeNic\PermissionRegistry\Events\ApprovalCompleted;
use ArcheeNic\PermissionRegistry\Notifications\ApprovalDecisionNotification;
use Illuminate\Support\Facades\Log;

class SendApprovalDecisionNotification
{
    public function handle(ApprovalCompleted $event): void
    {
        $userModel = config('permission-registry.user_model');
        if (!$userModel) {
            return;
        }

        $requester = $event->approvalRequest->requester;
        if (!$requester || !$requester->user_id) {
            return;
        }

        $user = $userModel::find($requester->user_id);
        if (!$user) {
            return;
        }

        try {
            $user->notify(new ApprovalDecisionNotification(
                $event->approvalRequest,
                $event->result,
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to send ApprovalDecisionNotification', [
                'user_id' => $user->id,
                'approval_request_id' => $event->approvalRequest->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
