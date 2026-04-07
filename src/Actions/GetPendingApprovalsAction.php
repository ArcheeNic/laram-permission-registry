<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Contracts\UserToVirtualUserResolver;
use ArcheeNic\PermissionRegistry\Enums\ApprovalRequestStatus;
use ArcheeNic\PermissionRegistry\Enums\ApproverType;
use ArcheeNic\PermissionRegistry\Models\ApprovalRequest;
use ArcheeNic\PermissionRegistry\Models\VirtualUserPosition;
use Illuminate\Database\Eloquent\Collection;

class GetPendingApprovalsAction
{
    public function __construct(
        private UserToVirtualUserResolver $userResolver
    ) {
    }

    /**
     * @param int $userId Application user id (users.id)
     * @return Collection<int, ApprovalRequest>
     */
    public function handle(int $userId): Collection
    {
        $virtualUserId = $this->userResolver->resolve($userId);
        if ($virtualUserId === null) {
            return new Collection([]);
        }

        $positionIds = VirtualUserPosition::where('virtual_user_id', $virtualUserId)
            ->pluck('position_id');

        return ApprovalRequest::where(ApprovalRequest::STATUS, ApprovalRequestStatus::PENDING->value)
            ->whereDoesntHave('decisions', function ($q) use ($userId) {
                $q->where('approver_id', $userId);
            })
            ->whereHas('approvalPolicy.approvers', function ($q) use ($virtualUserId, $positionIds) {
                $q->where(function ($sub) use ($virtualUserId) {
                    $sub->where('approver_type', ApproverType::VIRTUAL_USER->value)
                        ->where('approver_id', $virtualUserId);
                })->orWhere(function ($sub) use ($positionIds) {
                    $sub->where('approver_type', ApproverType::POSITION->value)
                        ->whereIn('approver_id', $positionIds);
                });
            })
            ->with([
                'grantedPermission.permission',
                'grantedPermission.user',
                'approvalPolicy',
                'decisions',
            ])
            ->orderBy('created_at')
            ->get();
    }
}
