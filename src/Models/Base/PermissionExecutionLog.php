<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class PermissionExecutionLog extends Model
{
    public const ID = 'id';
    public const GRANTED_PERMISSION_ID = 'granted_permission_id';
    public const PERMISSION_TRIGGER_ID = 'permission_trigger_id';
    public const EVENT_TYPE = 'event_type';
    public const STATUS = 'status';
    public const STARTED_AT = 'started_at';
    public const COMPLETED_AT = 'completed_at';
    public const ERROR_MESSAGE = 'error_message';
    public const META = 'meta';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::GRANTED_PERMISSION_ID,
        self::PERMISSION_TRIGGER_ID,
        self::EVENT_TYPE,
        self::STATUS,
        self::STARTED_AT,
        self::COMPLETED_AT,
        self::ERROR_MESSAGE,
        self::META,
    ];
}
