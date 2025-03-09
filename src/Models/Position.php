<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\Position as BasePosition;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends BasePosition
{
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Position::class, 'parent_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'position_permission');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(PermissionGroup::class, 'position_permission_group');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(VirtualUser::class, 'user_positions', 'position_id', 'user_id');
    }


}
