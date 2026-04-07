<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\PermissionTriggerAssignment as BasePermissionTriggerAssignment;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionTriggerAssignment extends BasePermissionTriggerAssignment
{
    protected $casts = [
        self::IS_ENABLED => 'boolean',
        self::CONFIG => 'array',
        self::ORDER => 'integer',
    ];

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    public function trigger(): BelongsTo
    {
        return $this->belongsTo(PermissionTrigger::class, 'permission_trigger_id');
    }
}
