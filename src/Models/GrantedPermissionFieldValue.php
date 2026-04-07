<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\GrantedPermissionFieldValue as BaseGrantedPermissionFieldValue;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrantedPermissionFieldValue extends BaseGrantedPermissionFieldValue
{
    public function grantedPermission(): BelongsTo
    {
        return $this->belongsTo(GrantedPermission::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(PermissionField::class, 'permission_field_id');
    }
}
