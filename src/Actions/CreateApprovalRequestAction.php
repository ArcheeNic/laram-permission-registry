<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\ApprovalRequestStatus;
use ArcheeNic\PermissionRegistry\Events\ApprovalRequested;
use ArcheeNic\PermissionRegistry\Models\ApprovalPolicy;
use ArcheeNic\PermissionRegistry\Models\ApprovalRequest;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use Illuminate\Support\Facades\Event;

class CreateApprovalRequestAction
{
    public function __construct(
        private ResolveApproversAction $resolveApprovers
    ) {
    }

    public function handle(
        GrantedPermission $grantedPermission,
        ApprovalPolicy $policy,
        ?int $requestedBy = null
    ): ApprovalRequest {
        $approvalRequest = ApprovalRequest::create([
            ApprovalRequest::GRANTED_PERMISSION_ID => $grantedPermission->id,
            ApprovalRequest::APPROVAL_POLICY_ID => $policy->id,
            ApprovalRequest::STATUS => ApprovalRequestStatus::PENDING->value,
            ApprovalRequest::REQUESTED_BY => $requestedBy,
        ]);

        $approverIds = $this->resolveApprovers->handle($policy);

        Event::dispatch(new ApprovalRequested($approvalRequest, $approverIds->toArray()));

        return $approvalRequest;
    }
}
