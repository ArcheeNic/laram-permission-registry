<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Enums\ApprovalType;
use ArcheeNic\PermissionRegistry\Models\Base\ApprovalPolicy as BaseApprovalPolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalPolicy extends BaseApprovalPolicy
{
    use HasFactory;

    protected static function newFactory()
    {
        return \ArcheeNic\PermissionRegistry\Database\Factories\ApprovalPolicyFactory::new();
    }

    protected $casts = [
        self::APPROVAL_TYPE => ApprovalType::class,
        self::IS_ACTIVE => 'boolean',
        self::REQUIRED_COUNT => 'integer',
    ];

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    public function approvers(): HasMany
    {
        return $this->hasMany(ApprovalPolicyApprover::class);
    }

    public function approvalRequests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class);
    }
}
