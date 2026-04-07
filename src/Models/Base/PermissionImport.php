<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class PermissionImport extends Model
{
    public const ID = 'id';
    public const NAME = 'name';
    public const CLASS_NAME = 'class_name';
    public const DESCRIPTION = 'description';
    public const IS_ACTIVE = 'is_active';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::NAME,
        self::CLASS_NAME,
        self::DESCRIPTION,
        self::IS_ACTIVE,
    ];
}
