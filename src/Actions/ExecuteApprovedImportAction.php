<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\ImportExecutionStatus;
use ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\ImportExecutionLog;
use ArcheeNic\PermissionRegistry\Models\ImportStagingRow;
use ArcheeNic\PermissionRegistry\Models\PermissionImport;
use ArcheeNic\PermissionRegistry\Services\ImportFieldMappingService;
use ArcheeNic\PermissionRegistry\Services\ImportTriggerConfigResolver;
use ArcheeNic\PermissionRegistry\Services\TriggerPermissionMatcherService;
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
        private ImportTriggerConfigResolver $importTriggerConfigResolver,
        private TriggerPermissionMatcherService $triggerPermissionMatcherService,
        private CleanupImportRunAction $cleanupAction,
    ) {}

    /**
     * @return array{created: int, updated: int, fired: int, synced: int, skipped: int, errors: int}
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
        [$triggerClassPatterns, $departmentFieldName, $fallbackTriggerClass] = $this->importTriggerConfigResolver->resolve($import);
        $managedPermissionIds = $this->triggerPermissionMatcherService->getAllManagedPermissionIds($triggerClassPatterns);
        $fallbackPermissionIds = $this->triggerPermissionMatcherService->getFallbackPermissionIds($fallbackTriggerClass);
        $managedPermissionIds = array_values(array_unique(array_merge($managedPermissionIds, $fallbackPermissionIds)));

        $stats = ['created' => 0, 'updated' => 0, 'fired' => 0, 'synced' => 0, 'skipped' => 0, 'errors' => 0];

        foreach ($rows as $row) {
            try {
                $matchStatus = $row->{ImportStagingRow::MATCH_STATUS};
                $status = $matchStatus instanceof ImportMatchStatus
                    ? $matchStatus
                    : ImportMatchStatus::from($matchStatus);

                match ($status) {
                    ImportMatchStatus::NEW => $this->processNewRow(
                        $row,
                        $mapping,
                        $triggerClassPatterns,
                        $departmentFieldName,
                        $fallbackPermissionIds,
                        $stats
                    ),
                    ImportMatchStatus::CHANGED => $this->processChangedRow(
                        $row,
                        $mapping,
                        $triggerClassPatterns,
                        $departmentFieldName,
                        $managedPermissionIds,
                        $fallbackPermissionIds,
                        $stats
                    ),
                    ImportMatchStatus::MISSING => $this->processMissingRow($row, $managedPermissionIds, $stats),
                    ImportMatchStatus::EXISTS => $this->processExistsRow(
                        $row,
                        $triggerClassPatterns,
                        $departmentFieldName,
                        $managedPermissionIds,
                        $fallbackPermissionIds,
                        $stats
                    ),
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

        return $stats;
    }

    /**
     * @param array<string, array{permission_field_id: int, is_internal: bool}> $mapping
     * @param array{created: int, updated: int, fired: int, synced: int, skipped: int, errors: int} $stats
     */
    private function processNewRow(
        ImportStagingRow $row,
        array $mapping,
        array $triggerClassPatterns,
        string $departmentFieldName,
        array $fallbackPermissionIds,
        array &$stats
    ): void {
        $globalFields = $this->buildGlobalFields($row, $mapping);

        $user = $this->createVirtualUserAction->handle($globalFields);
        $this->hireVirtualUserAction->handle(userId: $user->id, skipHrTriggers: true);

        $permissionIds = $this->resolvePermissionIdsFromRow($row, $triggerClassPatterns, $departmentFieldName, $fallbackPermissionIds);
        $existingPermissionIds = GrantedPermission::query()
            ->where(GrantedPermission::VIRTUAL_USER_ID, $user->id)
            ->whereIn(GrantedPermission::PERMISSION_ID, $permissionIds)
            ->pluck(GrantedPermission::PERMISSION_ID)
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        foreach (array_values(array_diff($permissionIds, $existingPermissionIds)) as $permissionId) {
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
     * @param array{created: int, updated: int, fired: int, synced: int, skipped: int, errors: int} $stats
     */
    private function processChangedRow(
        ImportStagingRow $row,
        array $mapping,
        array $triggerClassPatterns,
        string $departmentFieldName,
        array $managedPermissionIds,
        array $fallbackPermissionIds,
        array &$stats
    ): void {
        $virtualUserId = $row->{ImportStagingRow::MATCHED_VIRTUAL_USER_ID};
        $globalFields = $this->buildGlobalFields($row, $mapping);

        $this->updateGlobalFieldsAction->execute($virtualUserId, $globalFields);

        if ($virtualUserId !== null && $managedPermissionIds !== []) {
            $shouldHavePermissionIds = $this->resolvePermissionIdsFromRow($row, $triggerClassPatterns, $departmentFieldName, $fallbackPermissionIds);
            $currentPermissionIds = GrantedPermission::query()
                ->where(GrantedPermission::VIRTUAL_USER_ID, $virtualUserId)
                ->whereIn(GrantedPermission::PERMISSION_ID, $managedPermissionIds)
                ->pluck(GrantedPermission::PERMISSION_ID)
                ->map(static fn (mixed $id): int => (int) $id)
                ->all();

            $toGrant = array_values(array_diff($shouldHavePermissionIds, $currentPermissionIds));
            $toRevoke = array_values(array_diff($currentPermissionIds, $shouldHavePermissionIds));

            foreach ($toGrant as $permissionId) {
                $this->grantPermissionAction->handle(
                    userId: (int) $virtualUserId,
                    permissionId: $permissionId,
                    skipTriggers: true,
                    skipApprovalCheck: true,
                );
            }

            foreach ($toRevoke as $permissionId) {
                $this->revokePermissionAction->handle(
                    userId: (int) $virtualUserId,
                    permissionId: $permissionId,
                    skipTriggers: true,
                );
            }
        }

        $stats['updated']++;
    }

    /**
     * @param array{created: int, updated: int, fired: int, synced: int, skipped: int, errors: int} $stats
     */
    private function processExistsRow(
        ImportStagingRow $row,
        array $triggerClassPatterns,
        string $departmentFieldName,
        array $managedPermissionIds,
        array $fallbackPermissionIds,
        array &$stats
    ): void {
        $virtualUserId = $row->{ImportStagingRow::MATCHED_VIRTUAL_USER_ID};

        if ($virtualUserId === null || $managedPermissionIds === []) {
            $stats['skipped']++;

            return;
        }

        $shouldHavePermissionIds = $this->resolvePermissionIdsFromRow($row, $triggerClassPatterns, $departmentFieldName, $fallbackPermissionIds);
        $currentPermissionIds = GrantedPermission::query()
            ->where(GrantedPermission::VIRTUAL_USER_ID, $virtualUserId)
            ->whereIn(GrantedPermission::PERMISSION_ID, $managedPermissionIds)
            ->pluck(GrantedPermission::PERMISSION_ID)
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $toGrant = array_values(array_diff($shouldHavePermissionIds, $currentPermissionIds));
        $toRevoke = array_values(array_diff($currentPermissionIds, $shouldHavePermissionIds));

        foreach ($toGrant as $permissionId) {
            $this->grantPermissionAction->handle(
                userId: (int) $virtualUserId,
                permissionId: $permissionId,
                skipTriggers: true,
                skipApprovalCheck: true,
            );
        }

        foreach ($toRevoke as $permissionId) {
            $this->revokePermissionAction->handle(
                userId: (int) $virtualUserId,
                permissionId: $permissionId,
                skipTriggers: true,
            );
        }

        $stats['synced']++;
    }

    /**
     * @param array{created: int, updated: int, fired: int, synced: int, skipped: int, errors: int} $stats
     */
    private function processMissingRow(ImportStagingRow $row, array $managedPermissionIds, array &$stats): void
    {
        $virtualUserId = $row->{ImportStagingRow::MATCHED_VIRTUAL_USER_ID};

        if ($virtualUserId !== null) {
            $currentManagedPermissionIds = GrantedPermission::query()
                ->where(GrantedPermission::VIRTUAL_USER_ID, (int) $virtualUserId)
                ->whereIn(GrantedPermission::PERMISSION_ID, $managedPermissionIds)
                ->pluck(GrantedPermission::PERMISSION_ID)
                ->map(static fn (mixed $id): int => (int) $id)
                ->all();

            foreach ($currentManagedPermissionIds as $permissionId) {
                $this->revokePermissionAction->handle(
                    userId: (int) $virtualUserId,
                    permissionId: (int) $permissionId,
                    skipTriggers: true,
                );
            }
        }

        if ($virtualUserId !== null) {
            $this->fireVirtualUserAction->handle(userId: (int) $virtualUserId, skipHrTriggers: true);
        }

        $stats['fired']++;
    }

    /**
     * @param array<string, array{permission_field_id: int, is_internal: bool}> $mapping
     * @return array<int, mixed>
     */
    private function buildGlobalFields(ImportStagingRow $row, array $mapping): array
    {
        $fields = $this->extractRowFields($row);

        return $this->fieldMappingService->applyMapping($fields, $mapping);
    }

    /**
     * @param array<int, string> $triggerClassPatterns
     * @param array<int, int> $fallbackPermissionIds
     * @return array<int, int>
     */
    private function resolvePermissionIdsFromRow(
        ImportStagingRow $row,
        array $triggerClassPatterns,
        string $departmentFieldName,
        array $fallbackPermissionIds = []
    ): array {
        $fields = $this->extractRowFields($row);
        $departmentIds = $this->triggerPermissionMatcherService->normalizeDepartmentIds(
            $fields[$departmentFieldName] ?? null
        );

        $permissionIds = $this->triggerPermissionMatcherService
            ->matchByDepartments($departmentIds, $triggerClassPatterns)
            ->pluck('permission_id')
            ->map(static fn (mixed $permissionId): int => (int) $permissionId)
            ->unique()
            ->values()
            ->all();

        if ($permissionIds === []) {
            return array_values(array_unique($fallbackPermissionIds));
        }

        return $permissionIds;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractRowFields(ImportStagingRow $row): array
    {
        $fields = $row->{ImportStagingRow::FIELDS};
        if (is_array($fields)) {
            return $fields;
        }

        $decoded = json_decode((string) $fields, true);

        return is_array($decoded) ? $decoded : [];
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
     * @return array{created: int, updated: int, fired: int, synced: int, skipped: int, errors: int}
     */
    private function emptyStats(): array
    {
        return ['created' => 0, 'updated' => 0, 'fired' => 0, 'synced' => 0, 'skipped' => 0, 'errors' => 0];
    }
}
