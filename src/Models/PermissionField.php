<?php

namespace App\Modules\PermissionRegistry\Models;

use App\Modules\PermissionRegistry\Models\Base\PermissionField as BasePermissionField;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PermissionField extends BasePermissionField
{
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }
}
