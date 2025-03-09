<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class GrantedPermissionFieldValue extends Model
{
    public const ID = 'id';
    public const GRANTED_PERMISSION_ID = 'granted_permission_id';
    public const PERMISSION_FIELD_ID = 'permission_field_id';
    public const VALUE = 'value';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::GRANTED_PERMISSION_ID,
        self::PERMISSION_FIELD_ID,
        self::VALUE,
    ];
}
