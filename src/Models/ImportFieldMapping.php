<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\ImportFieldMapping as BaseImportFieldMapping;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportFieldMapping extends BaseImportFieldMapping
{
    protected $casts = [
        self::IS_INTERNAL => 'boolean',
    ];

    public function permissionImport(): BelongsTo
    {
        return $this->belongsTo(PermissionImport::class);
    }

    public function permissionField(): BelongsTo
    {
        return $this->belongsTo(PermissionField::class);
    }
}
