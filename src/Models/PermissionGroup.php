<?php

namespace App\Modules\PermissionRegistry\Models;

use App\Modules\PermissionRegistry\Models\Base\PermissionGroup as BasePermissionGroup;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PermissionGroup extends BasePermissionGroup
{
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
        return $this->belongsToMany(VirtualUser::class, 'user_groups', 'permission_group_id', 'user_id');
    }
}
