<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\PermissionExecutionLog as BasePermissionExecutionLog;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionExecutionLog extends BasePermissionExecutionLog
{
    protected $casts = [
        self::STARTED_AT => 'datetime',
        self::COMPLETED_AT => 'datetime',
        self::META => 'array',
    ];

    public function grantedPermission(): BelongsTo
    {
        return $this->belongsTo(GrantedPermission::class);
    }

    public function trigger(): BelongsTo
    {
        return $this->belongsTo(PermissionTrigger::class, 'permission_trigger_id');
    }
}
