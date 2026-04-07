<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\PermissionGroup as BasePermissionGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PermissionGroup extends BasePermissionGroup
{
    use HasFactory;

    protected static function newFactory()
    {
        return \ArcheeNic\PermissionRegistry\Database\Factories\PermissionGroupFactory::new();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(VirtualUser::class, 'virtual_user_groups', 'permission_group_id', 'virtual_user_id');
    }
}
