<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\UserGroup as BaseUserGroup;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGroup extends BaseUserGroup
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(VirtualUser::class, 'user_id');
    }

    public function permissionGroup(): BelongsTo
    {
        return $this->belongsTo(PermissionGroup::class);
    }
}
