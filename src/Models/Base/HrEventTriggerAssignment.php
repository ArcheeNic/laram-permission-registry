<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class HrEventTriggerAssignment extends Model
{
    protected $table = 'hr_event_trigger_assignments';

    public const ID = 'id';
    public const EVENT_TYPE = 'event_type';
    public const EMPLOYEE_CATEGORY = 'employee_category';
    public const PERMISSION_TRIGGER_ID = 'permission_trigger_id';
    public const ORDER = 'order';
    public const IS_ENABLED = 'is_enabled';
    public const CONFIG = 'config';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::EVENT_TYPE,
        self::EMPLOYEE_CATEGORY,
        self::PERMISSION_TRIGGER_ID,
        self::ORDER,
        self::IS_ENABLED,
        self::CONFIG,
    ];
}
