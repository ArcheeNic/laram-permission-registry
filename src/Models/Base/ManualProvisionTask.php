<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class ManualProvisionTask extends Model
{
    public const ID = 'id';

    public const GRANTED_PERMISSION_ID = 'granted_permission_id';

    public const ASSIGNED_TO = 'assigned_to';

    public const TITLE = 'title';

    public const DESCRIPTION = 'description';

    public const STATUS = 'status';

    public const DUE_AT = 'due_at';

    public const COMPLETED_AT = 'completed_at';

    public const COMPLETED_BY = 'completed_by';

    protected $fillable = [
        self::GRANTED_PERMISSION_ID,
        self::ASSIGNED_TO,
        self::TITLE,
        self::DESCRIPTION,
        self::STATUS,
        self::DUE_AT,
        self::COMPLETED_AT,
        self::COMPLETED_BY,
    ];
}
