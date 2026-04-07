<?php

namespace ArcheeNic\PermissionRegistry\Notifications;

use ArcheeNic\PermissionRegistry\Models\ApprovalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ApprovalRequest $approvalRequest,
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
        $requesterName = $this->approvalRequest->requester?->name ?? '';

        return (new MailMessage())
            ->subject(__('permission-registry::notifications.approval_requested_subject'))
            ->line(__('permission-registry::notifications.approval_requested_line', [
                'requester' => $requesterName,
                'permission' => $permissionName,
            ]))
            ->action(
                __('permission-registry::notifications.approval_requested_action'),
                url('/permission-registry/approvals')
            );
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $grantedPermission = $this->approvalRequest->grantedPermission;
        $permission = $grantedPermission?->permission;

        return [
            'type' => 'approval_requested',
            'approval_request_id' => $this->approvalRequest->id,
            'permission_name' => $permission?->name,
            'requester_name' => $this->approvalRequest->requester?->name,
        ];
    }
}
