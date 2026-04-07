<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Models\ApprovalPolicy;

class CheckApprovalRequiredAction
{
    public function handle(int $permissionId): ?ApprovalPolicy
    {
        return ApprovalPolicy::where(ApprovalPolicy::PERMISSION_ID, $permissionId)
            ->where(ApprovalPolicy::IS_ACTIVE, true)
            ->with('approvers')
            ->first();
    }
}
