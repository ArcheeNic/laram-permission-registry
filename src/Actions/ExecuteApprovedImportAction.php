<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\ImportExecutionStatus;
use ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus;
use ArcheeNic\PermissionRegistry\Models\ImportExecutionLog;
use ArcheeNic\PermissionRegistry\Models\ImportFieldMapping;
use ArcheeNic\PermissionRegistry\Models\ImportStagingRow;
use ArcheeNic\PermissionRegistry\Models\PermissionImport;
use ArcheeNic\PermissionRegistry\Services\ImportFieldMappingService;
use Illuminate\Support\Facades\Log;

class ExecuteApprovedImportAction
{
    public function __construct(
        private CreateVirtualUserAction $createVirtualUserAction,
        private HireVirtualUserAction $hireVirtualUserAction,
        private FireVirtualUserAction $fireVirtualUserAction,
        private GrantPermissionAction $grantPermissionAction,
        private RevokePermissionAction $revokePermissionAction,
        private UpdateVirtualUserGlobalFieldsAction $updateGlobalFieldsAction,
        private ImportFieldMappingService $fieldMappingService,
        private CleanupImportRunAction $cleanupAction,
    ) {}

    /**
     * @return array{created: int, updated: int, fired: int, skipped: int, errors: int}
     */
    public function handle(string $importRunId): array
    {
        $rows = ImportStagingRow::query()
            ->where(ImportStagingRow::IMPORT_RUN_ID, $importRunId)
            ->where(ImportStagingRow::IS_APPROVED, true)
            ->get();

        if ($rows->isEmpty()) {
            return $this->emptyStats();
        }

        $firstRow = $rows->first();
        $permissionImportId = $firstRow->{ImportStagingRow::PERMISSION_IMPORT_ID};
        $import = PermissionImport::query()->findOrFail($permissionImportId);
        $mapping = $this->fieldMappingService->getMapping($permissionImportId);
        $permissionId = $this->resolvePermissionId($permissionImportId);

        $stats = ['created' => 0, 'updated' => 0, 'fired' => 0, 'skipped' => 0, 'errors' => 0];

        foreach ($rows as $row) {
            try {
                $matchStatus = $row->{ImportStagingRow::MATCH_STATUS};
                $status = $matchStatus instanceof ImportMatchStatus
                    ? $matchStatus
                    : ImportMatchStatus::from($matchStatus);

                match ($status) {
                    ImportMatchStatus::NEW => $this->processNewRow($row, $mapping, $permissionId, $stats),
                    ImportMatchStatus::CHANGED => $this->processChangedRow($row, $mapping, $stats),
                    ImportMatchStatus::MISSING => $this->processMissingRow($row, $permissionId, $stats),
                    ImportMatchStatus::EXISTS => $stats['skipped']++,
                };
            } catch (\Throwable $e) {
                $stats['errors']++;
                Log::error('Import row processing failed', [
                    'import_run_id' => $importRunId,
                    'staging_row_id' => $row->id,
                    'match_status' => $row->{ImportStagingRow::MATCH_STATUS},
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->updateExecutionLog($importRunId, $stats);
        $this->cleanupAction->handle($importRunId);

        return $stats;
    }

    /**
     * @param array<string, array{permission_field_id: int, is_internal: bool}> $mapping
     * @param array{created: int, updated: int, fired: int, skipped: int, errors: int} $stats
     */
    private function processNewRow(ImportStagingRow $row, array $mapping, ?int $permissionId, array &$stats): void
    {
        $globalFields = $this->buildGlobalFields($row, $mapping);

        $user = $this->createVirtualUserAction->handle($globalFields);
        $this->hireVirtualUserAction->handle(userId: $user->id, skipHrTriggers: true);

        if ($permissionId !== null) {
            $this->grantPermissionAction->handle(
                userId: $user->id,
                permissionId: $permissionId,
                skipTriggers: true,
                skipApprovalCheck: true,
            );
        }

        $stats['created']++;
    }

    /**
     * @param array<string, array{permission_field_id: int, is_internal: bool}> $mapping
     * @param array{created: int, updated: int, fired: int, skipped: int, errors: int} $stats
     */
    private function processChangedRow(ImportStagingRow $row, array $mapping, array &$stats): void
    {
        $virtualUserId = $row->{ImportStagingRow::MATCHED_VIRTUAL_USER_ID};
        $globalFields = $this->buildGlobalFields($row, $mapping);

        $this->updateGlobalFieldsAction->execute($virtualUserId, $globalFields);

        $stats['updated']++;
    }

    /**
     * @param array{created: int, updated: int, fired: int, skipped: int, errors: int} $stats
     */
    private function processMissingRow(ImportStagingRow $row, ?int $permissionId, array &$stats): void
    {
        $virtualUserId = $row->{ImportStagingRow::MATCHED_VIRTUAL_USER_ID};

        if ($permissionId !== null) {
            $this->revokePermissionAction->handle(
                userId: $virtualUserId,
                permissionId: $permissionId,
                skipTriggers: true,
            );
        }

        $this->fireVirtualUserAction->handle(userId: $virtualUserId, skipHrTriggers: true);

        $stats['fired']++;
    }

    /**
     * @param array<string, array{permission_field_id: int, is_internal: bool}> $mapping
     * @return array<int, mixed>
     */
    private function buildGlobalFields(ImportStagingRow $row, array $mapping): array
    {
        $fields = $row->{ImportStagingRow::FIELDS};
        if (!is_array($fields)) {
            $fields = json_decode($fields, true) ?? [];
        }

        return $this->fieldMappingService->applyMapping($fields, $mapping);
    }

    private function resolvePermissionId(int $permissionImportId): ?int
    {
        $mapping = ImportFieldMapping::query()
            ->where(ImportFieldMapping::PERMISSION_IMPORT_ID, $permissionImportId)
            ->with('permissionField.permissions')
            ->first();

        $permission = $mapping?->permissionField?->permissions?->first();

        return $permission?->id;
    }

    private function updateExecutionLog(string $importRunId, array $stats): void
    {
        ImportExecutionLog::query()
            ->where(ImportExecutionLog::IMPORT_RUN_ID, $importRunId)
            ->latest()
            ->first()
            ?->update([
                ImportExecutionLog::STATUS => ImportExecutionStatus::COMPLETED->value,
                ImportExecutionLog::COMPLETED_AT => now(),
                ImportExecutionLog::STATS => $stats,
            ]);
    }

    /**
     * @return array{created: int, updated: int, fired: int, skipped: int, errors: int}
     */
    private function emptyStats(): array
    {
        return ['created' => 0, 'updated' => 0, 'fired' => 0, 'skipped' => 0, 'errors' => 0];
    }
}
