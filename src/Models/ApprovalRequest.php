<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Enums\ApprovalRequestStatus;
use ArcheeNic\PermissionRegistry\Models\Base\ApprovalRequest as BaseApprovalRequest;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalRequest extends BaseApprovalRequest
{
    protected $casts = [
        self::STATUS => ApprovalRequestStatus::class,
        self::RESOLVED_AT => 'datetime',
    ];

    public function grantedPermission(): BelongsTo
    {
        return $this->belongsTo(GrantedPermission::class);
    }

    public function approvalPolicy(): BelongsTo
    {
        return $this->belongsTo(ApprovalPolicy::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(ApprovalDecision::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(VirtualUser::class, 'requested_by');
    }
}
