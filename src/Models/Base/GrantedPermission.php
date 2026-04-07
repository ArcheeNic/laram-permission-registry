<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class GrantedPermission extends Model
{
    public const ID = 'id';
    public const VIRTUAL_USER_ID = 'virtual_user_id';
    public const PERMISSION_ID = 'permission_id';
    public const STATUS = 'status';
    public const STATUS_MESSAGE = 'status_message';
    public const ENABLED = 'enabled';
    public const META = 'meta';
    public const GRANTED_AT = 'granted_at';
    public const EXPIRES_AT = 'expires_at';
    public const REQUESTED_BY = 'requested_by';
    public const CONFIRMED_BY = 'confirmed_by';
    public const CONFIRMED_AT = 'confirmed_at';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::VIRTUAL_USER_ID,
        self::PERMISSION_ID,
        self::STATUS,
        self::STATUS_MESSAGE,
        self::ENABLED,
        self::META,
        self::GRANTED_AT,
        self::EXPIRES_AT,
        self::REQUESTED_BY,
        self::CONFIRMED_BY,
        self::CONFIRMED_AT,
    ];
}
