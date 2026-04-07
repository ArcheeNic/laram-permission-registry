<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Models\ImportStagingRow;

class ApproveImportRowsAction
{
    /**
     * @param int[] $approvedRowIds
     */
    public function handle(string $importRunId, array $approvedRowIds): int
    {
        ImportStagingRow::query()
            ->where(ImportStagingRow::IMPORT_RUN_ID, $importRunId)
            ->whereIn(ImportStagingRow::ID, $approvedRowIds)
            ->update([ImportStagingRow::IS_APPROVED => true]);

        ImportStagingRow::query()
            ->where(ImportStagingRow::IMPORT_RUN_ID, $importRunId)
            ->whereNotIn(ImportStagingRow::ID, $approvedRowIds)
            ->update([ImportStagingRow::IS_APPROVED => false]);

        return ImportStagingRow::query()
            ->where(ImportStagingRow::IMPORT_RUN_ID, $importRunId)
            ->where(ImportStagingRow::IS_APPROVED, true)
            ->count();
    }
}
