<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    public const ID = 'id';

    public const SERVICE = 'service';

    public const NAME = 'name';

    public const DESCRIPTION = 'description';

    public const TAGS = 'tags';

    public const AUTO_GRANT = 'auto_grant';

    public const AUTO_REVOKE = 'auto_revoke';

    public const MANAGEMENT_MODE = 'management_mode';

    public const RISK_LEVEL = 'risk_level';

    public const SYSTEM_OWNER_VIRTUAL_USER_ID = 'system_owner_virtual_user_id';

    public const ATTESTATION_PERIOD_DAYS = 'attestation_period_days';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::SERVICE,
        self::NAME,
        self::DESCRIPTION,
        self::TAGS,
        self::AUTO_GRANT,
        self::AUTO_REVOKE,
        self::MANAGEMENT_MODE,
        self::RISK_LEVEL,
        self::SYSTEM_OWNER_VIRTUAL_USER_ID,
        self::ATTESTATION_PERIOD_DAYS,
    ];

    protected $casts = [
        self::AUTO_GRANT => 'boolean',
        self::AUTO_REVOKE => 'boolean',
    ];
}
