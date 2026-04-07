<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Models\ImportStagingRow;

class CleanupImportRunAction
{
    public function handle(string $importRunId): int
    {
        return ImportStagingRow::query()
            ->where(ImportStagingRow::IMPORT_RUN_ID, $importRunId)
            ->delete();
    }
}
