<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class VirtualUserGroup extends Model
{
    protected $table = 'virtual_user_groups';

    public const ID = 'id';
    public const VIRTUAL_USER_ID = 'virtual_user_id';
    public const PERMISSION_GROUP_ID = 'permission_group_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::VIRTUAL_USER_ID,
        self::PERMISSION_GROUP_ID,
    ];
}
