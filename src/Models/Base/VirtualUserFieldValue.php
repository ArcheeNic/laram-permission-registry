<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

/**
 * Base model for VirtualUserFieldValue
 * 
 * Note: Always use 'virtual_users' naming convention in this module
 * to avoid conflicts with application's 'users' table.
 */
class VirtualUserFieldValue extends Model
{
    public const ID = 'id';
    public const VIRTUAL_USER_ID = 'virtual_user_id';
    public const PERMISSION_FIELD_ID = 'permission_field_id';
    public const VALUE = 'value';
    public const SOURCE = 'source';
    public const CREATED_BY = 'created_by';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::VIRTUAL_USER_ID,
        self::PERMISSION_FIELD_ID,
        self::VALUE,
        self::SOURCE,
        self::CREATED_BY,
    ];
}
