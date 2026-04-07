<?php

namespace ArcheeNic\PermissionRegistry\Console;

use ArcheeNic\PermissionRegistry\Enums\ApprovalRequestStatus;
use ArcheeNic\PermissionRegistry\Enums\GrantedPermissionStatus;
use ArcheeNic\PermissionRegistry\Models\ApprovalRequest;
use Illuminate\Console\Command;

class ExpireApprovalRequestsCommand extends Command
{
    protected $signature = 'permission-registry:expire-approvals {--days=30 : Days before expiration}';
    protected $description = 'Expire pending approval requests older than specified days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $requests = ApprovalRequest::where('status', ApprovalRequestStatus::PENDING->value)
            ->where('created_at', '<', $cutoff)
            ->get();

        $count = 0;
        foreach ($requests as $request) {
            $request->update([
                'status' => ApprovalRequestStatus::EXPIRED->value,
                'resolved_at' => now(),
            ]);

            if ($request->grantedPermission) {
                $request->grantedPermission->update([
                    'status' => GrantedPermissionStatus::REJECTED->value,
                    'status_message' => __('permission-registry::messages.approval_request_expired'),
                ]);
            }

            $count++;
        }

        $this->info(__('permission-registry::messages.expired_count', ['count' => $count]));

        return self::SUCCESS;
    }
}
