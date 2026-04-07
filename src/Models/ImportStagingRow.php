<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus;
use ArcheeNic\PermissionRegistry\Models\Base\ImportStagingRow as BaseImportStagingRow;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportStagingRow extends BaseImportStagingRow
{
    protected $casts = [
        self::FIELDS => 'array',
        self::IS_APPROVED => 'boolean',
        self::MATCH_STATUS => ImportMatchStatus::class,
    ];

    public function permissionImport(): BelongsTo
    {
        return $this->belongsTo(PermissionImport::class);
    }

    public function matchedVirtualUser(): BelongsTo
    {
        return $this->belongsTo(VirtualUser::class, self::MATCHED_VIRTUAL_USER_ID);
    }
}
