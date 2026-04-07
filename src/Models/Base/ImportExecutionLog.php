<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class ImportExecutionLog extends Model
{
    public const ID = 'id';
    public const IMPORT_RUN_ID = 'import_run_id';
    public const PERMISSION_IMPORT_ID = 'permission_import_id';
    public const STATUS = 'status';
    public const STARTED_AT = 'started_at';
    public const COMPLETED_AT = 'completed_at';
    public const STATS = 'stats';
    public const ERROR_MESSAGE = 'error_message';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::IMPORT_RUN_ID,
        self::PERMISSION_IMPORT_ID,
        self::STATUS,
        self::STARTED_AT,
        self::COMPLETED_AT,
        self::STATS,
        self::ERROR_MESSAGE,
    ];
}
