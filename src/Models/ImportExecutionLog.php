<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Enums\ImportExecutionStatus;
use ArcheeNic\PermissionRegistry\Models\Base\ImportExecutionLog as BaseImportExecutionLog;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportExecutionLog extends BaseImportExecutionLog
{
    protected $casts = [
        self::STATS => 'array',
        self::STARTED_AT => 'datetime',
        self::COMPLETED_AT => 'datetime',
        self::STATUS => ImportExecutionStatus::class,
    ];

    public function permissionImport(): BelongsTo
    {
        return $this->belongsTo(PermissionImport::class);
    }
}
