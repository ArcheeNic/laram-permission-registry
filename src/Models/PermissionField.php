<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\PermissionField as BasePermissionField;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PermissionField extends BasePermissionField
{
    use HasFactory;

    protected static function newFactory()
    {
        return \ArcheeNic\PermissionRegistry\Database\Factories\PermissionFieldFactory::new();
    }

    protected $casts = [
        self::IS_GLOBAL => 'boolean',
        self::REQUIRED_ON_USER_CREATE => 'boolean',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }
}
