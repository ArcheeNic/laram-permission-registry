<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class PermissionAuditLog extends Model
{
    public const ID = 'id';

    public const ACTION = 'action';

    public const ACTOR_ID = 'actor_id';

    public const VIRTUAL_USER_ID = 'virtual_user_id';

    public const PERMISSION_ID = 'permission_id';

    public const IP_ADDRESS = 'ip_address';

    public const USER_AGENT = 'user_agent';

    public const FIELD_VALUES = 'field_values';

    public const META = 'meta';

    public const CONTEXT = 'context';

    public const CREATED_AT = 'created_at';

    public $timestamps = false;

    protected $fillable = [
        self::ACTION,
        self::ACTOR_ID,
        self::VIRTUAL_USER_ID,
        self::PERMISSION_ID,
        self::IP_ADDRESS,
        self::USER_AGENT,
        self::FIELD_VALUES,
        self::META,
        self::CONTEXT,
        self::CREATED_AT,
    ];
}
