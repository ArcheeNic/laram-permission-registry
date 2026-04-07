<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Enums\ManualTaskStatus;
use ArcheeNic\PermissionRegistry\Models\Base\ManualProvisionTask as BaseManualProvisionTask;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManualProvisionTask extends BaseManualProvisionTask
{
    protected $casts = [
        self::STATUS => ManualTaskStatus::class,
        self::DUE_AT => 'datetime',
        self::COMPLETED_AT => 'datetime',
    ];

    public function grantedPermission(): BelongsTo
    {
        return $this->belongsTo(GrantedPermission::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(VirtualUser::class, 'assigned_to');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(AccessEvidence::class);
    }
}
