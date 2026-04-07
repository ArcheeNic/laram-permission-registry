<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class ImportStagingRow extends Model
{
    public const ID = 'id';
    public const IMPORT_RUN_ID = 'import_run_id';
    public const PERMISSION_IMPORT_ID = 'permission_import_id';
    public const EXTERNAL_ID = 'external_id';
    public const FIELDS = 'fields';
    public const MATCH_STATUS = 'match_status';
    public const MATCHED_VIRTUAL_USER_ID = 'matched_virtual_user_id';
    public const IS_APPROVED = 'is_approved';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::IMPORT_RUN_ID,
        self::PERMISSION_IMPORT_ID,
        self::EXTERNAL_ID,
        self::FIELDS,
        self::MATCH_STATUS,
        self::MATCHED_VIRTUAL_USER_ID,
        self::IS_APPROVED,
    ];
}
