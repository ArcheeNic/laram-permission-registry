<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\VirtualUserFieldValue as BaseVirtualUserFieldValue;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Global field values for virtual users
 * 
 * Stores values for fields marked as is_global=true.
 * These values are shared across all permissions for a user.
 */
class VirtualUserFieldValue extends BaseVirtualUserFieldValue
{
    public function virtualUser(): BelongsTo
    {
        return $this->belongsTo(VirtualUser::class, 'virtual_user_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(PermissionField::class, 'permission_field_id');
    }
}
