<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\PermissionImport as BasePermissionImport;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermissionImport extends BasePermissionImport
{
    protected $casts = [
        self::IS_ACTIVE => 'boolean',
    ];

    public function fieldMappings(): HasMany
    {
        return $this->hasMany(ImportFieldMapping::class);
    }

    public function stagingRows(): HasMany
    {
        return $this->hasMany(ImportStagingRow::class);
    }

    public function executionLogs(): HasMany
    {
        return $this->hasMany(ImportExecutionLog::class);
    }
}
