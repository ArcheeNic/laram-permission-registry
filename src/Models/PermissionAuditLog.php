<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\PermissionAuditLog as BasePermissionAuditLog;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionAuditLog extends BasePermissionAuditLog
{
    protected $casts = [
        self::FIELD_VALUES => 'array',
        self::META => 'array',
        self::CONTEXT => 'array',
        self::CREATED_AT => 'datetime',
    ];

    public function virtualUser(): BelongsTo
    {
        return $this->belongsTo(VirtualUser::class, self::VIRTUAL_USER_ID);
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, self::PERMISSION_ID)->withTrashed();
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(VirtualUser::class, self::ACTOR_ID);
    }
}
