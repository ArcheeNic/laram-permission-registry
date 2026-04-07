<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class AccessAttestation extends Model
{
    public const ID = 'id';

    public const GRANTED_PERMISSION_ID = 'granted_permission_id';

    public const ATTESTATION_PERIOD_DAYS = 'attestation_period_days';

    public const DUE_AT = 'due_at';

    public const STATUS = 'status';

    public const DECIDED_BY = 'decided_by';

    public const DECIDED_AT = 'decided_at';

    public const COMMENT = 'comment';

    protected $fillable = [
        self::GRANTED_PERMISSION_ID,
        self::ATTESTATION_PERIOD_DAYS,
        self::DUE_AT,
        self::STATUS,
        self::DECIDED_BY,
        self::DECIDED_AT,
        self::COMMENT,
    ];
}
