<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    public const ID = 'id';
    public const USER_ID = 'user_id';
    public const PERMISSION_GROUP_ID = 'permission_group_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::USER_ID,
        self::PERMISSION_GROUP_ID,
    ];
}
