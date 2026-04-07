<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class PermissionTrigger extends Model
{
    public const ID = 'id';
    public const NAME = 'name';
    public const CLASS_NAME = 'class_name';
    public const DESCRIPTION = 'description';
    public const TYPE = 'type';
    public const IS_ACTIVE = 'is_active';
    public const IS_CONFIGURED = 'is_configured';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::NAME,
        self::CLASS_NAME,
        self::DESCRIPTION,
        self::TYPE,
        self::IS_ACTIVE,
        self::IS_CONFIGURED,
    ];
}
