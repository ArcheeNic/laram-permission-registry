<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Enums\ApprovalDecisionType;
use ArcheeNic\PermissionRegistry\Models\Base\ApprovalDecision as BaseApprovalDecision;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalDecision extends BaseApprovalDecision
{
    protected $casts = [
        self::DECISION => ApprovalDecisionType::class,
        self::DECIDED_AT => 'datetime',
    ];

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function approver(): BelongsTo
    {
        $model = config('permission-registry.user_model') ?? VirtualUser::class;

        return $this->belongsTo($model, 'approver_id');
    }
}
