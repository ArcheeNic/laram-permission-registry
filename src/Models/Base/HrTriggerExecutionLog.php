<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class HrTriggerExecutionLog extends Model
{
    protected $table = 'hr_trigger_execution_logs';

    public const ID = 'id';
    public const VIRTUAL_USER_ID = 'virtual_user_id';
    public const HR_EVENT_TRIGGER_ASSIGNMENT_ID = 'hr_event_trigger_assignment_id';
    public const PERMISSION_TRIGGER_ID = 'permission_trigger_id';
    public const EVENT_TYPE = 'event_type';
    public const EMPLOYEE_CATEGORY = 'employee_category';
    public const STATUS = 'status';
    public const STARTED_AT = 'started_at';
    public const COMPLETED_AT = 'completed_at';
    public const ERROR_MESSAGE = 'error_message';
    public const META = 'meta';
    public const RESOLUTION_CONTEXT = 'resolution_context';
    public const ACTOR_ID = 'actor_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::VIRTUAL_USER_ID,
        self::HR_EVENT_TRIGGER_ASSIGNMENT_ID,
        self::PERMISSION_TRIGGER_ID,
        self::EVENT_TYPE,
        self::EMPLOYEE_CATEGORY,
        self::STATUS,
        self::STARTED_AT,
        self::COMPLETED_AT,
        self::ERROR_MESSAGE,
        self::META,
        self::RESOLUTION_CONTEXT,
        self::ACTOR_ID,
    ];
}
