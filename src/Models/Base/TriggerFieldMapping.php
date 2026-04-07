<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class TriggerFieldMapping extends Model
{
    public const ID = 'id';
    public const PERMISSION_TRIGGER_ID = 'permission_trigger_id';
    public const TRIGGER_FIELD_NAME = 'trigger_field_name';
    public const PERMISSION_FIELD_ID = 'permission_field_id';
    public const IS_INTERNAL = 'is_internal';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::PERMISSION_TRIGGER_ID,
        self::TRIGGER_FIELD_NAME,
        self::PERMISSION_FIELD_ID,
        self::IS_INTERNAL,
    ];
}

