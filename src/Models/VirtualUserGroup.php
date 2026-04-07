<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\VirtualUserGroup as BaseVirtualUserGroup;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualUserGroup extends BaseVirtualUserGroup
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(VirtualUser::class, 'virtual_user_id');
    }

    public function permissionGroup(): BelongsTo
    {
        return $this->belongsTo(PermissionGroup::class);
    }
}
