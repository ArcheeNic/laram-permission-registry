<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class ImportFieldMapping extends Model
{
    public const ID = 'id';
    public const PERMISSION_IMPORT_ID = 'permission_import_id';
    public const IMPORT_FIELD_NAME = 'import_field_name';
    public const PERMISSION_FIELD_ID = 'permission_field_id';
    public const IS_INTERNAL = 'is_internal';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::PERMISSION_IMPORT_ID,
        self::IMPORT_FIELD_NAME,
        self::PERMISSION_FIELD_ID,
        self::IS_INTERNAL,
    ];
}
