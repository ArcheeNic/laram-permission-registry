<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\PermissionTrigger as BasePermissionTrigger;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermissionTrigger extends BasePermissionTrigger
{
    protected $casts = [
        self::IS_ACTIVE => 'boolean',
        self::IS_CONFIGURED => 'boolean',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(PermissionTriggerAssignment::class);
    }

    public function hrEventAssignments(): HasMany
    {
        return $this->hasMany(HrEventTriggerAssignment::class);
    }

    public function executionLogs(): HasMany
    {
        return $this->hasMany(PermissionExecutionLog::class);
    }

    public function fieldMappings(): HasMany
    {
        return $this->hasMany(TriggerFieldMapping::class);
    }
}
