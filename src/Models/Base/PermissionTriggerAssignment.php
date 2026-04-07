<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class PermissionTriggerAssignment extends Model
{
    public const ID = 'id';
    public const PERMISSION_ID = 'permission_id';
    public const PERMISSION_TRIGGER_ID = 'permission_trigger_id';
    public const EVENT_TYPE = 'event_type';
    public const ORDER = 'order';
    public const IS_ENABLED = 'is_enabled';
    public const CONFIG = 'config';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::PERMISSION_ID,
        self::PERMISSION_TRIGGER_ID,
        self::EVENT_TYPE,
        self::ORDER,
        self::IS_ENABLED,
        self::CONFIG,
    ];
}
