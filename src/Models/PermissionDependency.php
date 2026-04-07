<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\PermissionDependency as BasePermissionDependency;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionDependency extends BasePermissionDependency
{
    protected $casts = [
        self::IS_STRICT => 'boolean',
    ];

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    public function requiredPermission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'required_permission_id');
    }
}
