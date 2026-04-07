<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Enums\ApproverType;
use ArcheeNic\PermissionRegistry\Models\Base\ApprovalPolicyApprover as BaseApprovalPolicyApprover;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalPolicyApprover extends BaseApprovalPolicyApprover
{
    protected $casts = [
        self::APPROVER_TYPE => ApproverType::class,
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(ApprovalPolicy::class, 'approval_policy_id');
    }
}
