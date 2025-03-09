<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class GrantedPermission extends Model
{
    public const ID = 'id';
    public const USER_ID = 'user_id';
    public const PERMISSION_ID = 'permission_id';
    public const ENABLED = 'enabled';
    public const META = 'meta';
    public const GRANTED_AT = 'granted_at';
    public const EXPIRES_AT = 'expires_at';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::USER_ID,
        self::PERMISSION_ID,
        self::ENABLED,
        self::META,
        self::GRANTED_AT,
        self::EXPIRES_AT,
    ];
}
