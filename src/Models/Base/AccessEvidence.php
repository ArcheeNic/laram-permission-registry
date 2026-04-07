<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class AccessEvidence extends Model
{
    public const ID = 'id';

    public const GRANTED_PERMISSION_ID = 'granted_permission_id';

    public const MANUAL_PROVISION_TASK_ID = 'manual_provision_task_id';

    public const TYPE = 'type';

    public const VALUE = 'value';

    public const META = 'meta';

    public const PROVIDED_BY = 'provided_by';

    protected $table = 'access_evidences';

    protected $fillable = [
        self::GRANTED_PERMISSION_ID,
        self::MANUAL_PROVISION_TASK_ID,
        self::TYPE,
        self::VALUE,
        self::META,
        self::PROVIDED_BY,
    ];
}
