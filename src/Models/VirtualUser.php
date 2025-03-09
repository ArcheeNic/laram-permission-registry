<?php

namespace App\Modules\PermissionRegistry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VirtualUser extends Model
{
    protected $table = 'virtual_users';

    protected $fillable = [
        'name',
        'email',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * Позиции пользователя
     */
    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'user_positions', 'user_id');
    }

    /**
     * Группы пользователя
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(PermissionGroup::class, 'user_groups', 'user_id', 'permission_group_id');
    }

    /**
     * Выданные права пользователя
     */
    public function grantedPermissions(): HasMany
    {
        return $this->hasMany(GrantedPermission::class, 'user_id');
    }
}
