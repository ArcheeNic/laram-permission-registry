<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class ApprovalPolicy extends Model
{
    public const ID = 'id';
    public const PERMISSION_ID = 'permission_id';
    public const APPROVAL_TYPE = 'approval_type';
    public const REQUIRED_COUNT = 'required_count';
    public const IS_ACTIVE = 'is_active';

    protected $fillable = [
        self::PERMISSION_ID,
        self::APPROVAL_TYPE,
        self::REQUIRED_COUNT,
        self::IS_ACTIVE,
    ];
}
