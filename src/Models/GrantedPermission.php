<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\GrantedPermission as BaseGrantedPermission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Granted Permission Model
 *
 * Represents a permission granted to a VirtualUser.
 * Note: Uses 'virtual_user_id' column that references 'virtual_users' table,
 * following the module's naming convention.
 */
class GrantedPermission extends BaseGrantedPermission
{
    use HasFactory;

    protected static function newFactory()
    {
        return \ArcheeNic\PermissionRegistry\Database\Factories\GrantedPermissionFactory::new();
    }

    protected $casts = [
        self::META => 'array',
        self::ENABLED => 'boolean',
        self::GRANTED_AT => 'datetime',
        self::EXPIRES_AT => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(VirtualUser::class, 'virtual_user_id');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    public function fieldValues(): HasMany
    {
        return $this->hasMany(GrantedPermissionFieldValue::class);
    }

    public function executionLogs(): HasMany
    {
        return $this->hasMany(PermissionExecutionLog::class);
    }

    public function approvalRequests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class);
    }

    public function latestApprovalRequest(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ApprovalRequest::class)->latestOfMany();
    }

    public function manualProvisionTasks(): HasMany
    {
        return $this->hasMany(ManualProvisionTask::class);
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(AccessEvidence::class);
    }

    public function attestations(): HasMany
    {
        return $this->hasMany(AccessAttestation::class);
    }

    public function latestAttestation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AccessAttestation::class)->latestOfMany();
    }
}
