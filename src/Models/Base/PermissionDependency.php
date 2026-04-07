<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class PermissionDependency extends Model
{
    public const ID = 'id';
    public const PERMISSION_ID = 'permission_id';
    public const REQUIRED_PERMISSION_ID = 'required_permission_id';
    public const IS_STRICT = 'is_strict';
    public const EVENT_TYPE = 'event_type';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::PERMISSION_ID,
        self::REQUIRED_PERMISSION_ID,
        self::IS_STRICT,
        self::EVENT_TYPE,
    ];
}
