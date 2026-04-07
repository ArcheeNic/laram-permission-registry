<?php

namespace ArcheeNic\PermissionRegistry\Notifications;

use ArcheeNic\PermissionRegistry\Enums\ApprovalRequestStatus;
use ArcheeNic\PermissionRegistry\Models\ApprovalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ApprovalRequest $approvalRequest,
        private readonly ApprovalRequestStatus $result,
    ) {
    }

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $permissionName = $this->approvalRequest->grantedPermission?->permission?->name ?? '';
        $decision = $this->result === ApprovalRequestStatus::APPROVED
            ? __('permission-registry::notifications.decision_approved')
            : __('permission-registry::notifications.decision_rejected');

        return (new MailMessage())
            ->subject(__('permission-registry::notifications.approval_decision_subject'))
            ->line(__('permission-registry::notifications.approval_decision_line', [
                'permission' => $permissionName,
                'decision' => $decision,
            ]))
            ->action(
                __('permission-registry::notifications.approval_decision_action'),
                url('/permission-registry/my/requests')
            );
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $permission = $this->approvalRequest->grantedPermission?->permission;

        return [
            'type' => 'approval_decision',
            'approval_request_id' => $this->approvalRequest->id,
            'permission_name' => $permission?->name,
            'decision' => $this->result->value,
            'comment' => $this->approvalRequest->comment,
        ];
    }
}
