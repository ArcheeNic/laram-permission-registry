<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\Permission as BasePermission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends BasePermission
{
    protected $casts = [
        self::TAGS => 'array',
    ];

    public function fields(): BelongsToMany
    {
        return $this->belongsToMany(PermissionField::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(PermissionGroup::class);
    }
}
