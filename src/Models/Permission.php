<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Enums\ManagementMode;
use ArcheeNic\PermissionRegistry\Enums\RiskLevel;
use ArcheeNic\PermissionRegistry\Models\Base\Permission as BasePermission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Permission extends BasePermission
{
    use HasFactory;
    use SoftDeletes;

    protected static function newFactory()
    {
        return \ArcheeNic\PermissionRegistry\Database\Factories\PermissionFactory::new();
    }

    protected $casts = [
        self::TAGS => 'array',
        self::MANAGEMENT_MODE => ManagementMode::class,
        self::RISK_LEVEL => RiskLevel::class,
        self::ATTESTATION_PERIOD_DAYS => 'integer',
    ];

    public function fields(): BelongsToMany
    {
        return $this->belongsToMany(PermissionField::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(PermissionGroup::class);
    }

    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'position_permission');
    }

    public function triggerAssignments(): HasMany
    {
        return $this->hasMany(PermissionTriggerAssignment::class);
    }

    public function grantTriggers(): HasMany
    {
        return $this->hasMany(PermissionTriggerAssignment::class)
            ->where('event_type', 'grant')
            ->orderBy('order');
    }

    public function revokeTriggers(): HasMany
    {
        return $this->hasMany(PermissionTriggerAssignment::class)
            ->where('event_type', 'revoke')
            ->orderBy('order');
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(PermissionDependency::class);
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(PermissionDependency::class, 'required_permission_id');
    }

    public function approvalPolicy(): HasOne
    {
        return $this->hasOne(ApprovalPolicy::class);
    }

    public function systemOwner(): BelongsTo
    {
        return $this->belongsTo(VirtualUser::class, 'system_owner_virtual_user_id');
    }

    public function grantDependencies(): HasMany
    {
        return $this->hasMany(PermissionDependency::class)
            ->where('event_type', 'grant');
    }

    public function revokeDependencies(): HasMany
    {
        return $this->hasMany(PermissionDependency::class)
            ->where('event_type', 'revoke');
    }

    public function grantedPermissions(): HasMany
    {
        return $this->hasMany(GrantedPermission::class);
    }
}
