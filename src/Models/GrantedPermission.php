<?php

namespace App\Modules\PermissionRegistry\Models;

use App\Modules\PermissionRegistry\Models\Base\GrantedPermission as BaseGrantedPermission;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrantedPermission extends BaseGrantedPermission
{
    protected $casts = [
        self::META => 'array',
        self::ENABLED => 'boolean',
        self::GRANTED_AT => 'datetime',
        self::EXPIRES_AT => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(VirtualUser::class, 'user_id');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    public function fieldValues(): HasMany
    {
        return $this->hasMany(GrantedPermissionFieldValue::class);
    }
}
