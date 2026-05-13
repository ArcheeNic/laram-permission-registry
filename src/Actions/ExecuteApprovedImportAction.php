<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\ImportExecutionStatus;
use ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus;
use ArcheeNic\PermissionRegistry\Enums\PermissionScope;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionResource;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Models\ImportExecutionLog;
use ArcheeNic\PermissionRegistry\Models\ImportStagingRow;
use ArcheeNic\PermissionRegistry\Models\PermissionImport;
use ArcheeNic\PermissionRegistry\Services\ImportFieldMappingService;
use ArcheeNic\PermissionRegistry\Services\ImportTriggerConfigResolver;
use ArcheeNic\PermissionRegistry\Services\TriggerPermissionMatcherService;
use ArcheeNic\PermissionRegistry\Services\UserAutoGrantPairsCollector;
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

        $shouldHavePairs = $this->resolvePermissionPairsFromRow(
            $row,
            $triggerClassPatterns,
            $departmentFieldName,
            $fallbackPermissionIds
        );

        $existingKeys = $this->existingPairKeys((int) $user->id, $this->permissionIdsFromPairs($shouldHavePairs));

        foreach ($shouldHavePairs as $pair) {
            $key = UserAutoGrantPairsCollector::key($pair['permission_id'], $pair['resource_id']);
            if (isset($existingKeys[$key])) {
                continue;
            }
            $this->grantPermissionAction->handle(
                userId: (int) $user->id,
                permissionId: $pair['permission_id'],
                skipTriggers: true,
                skipApprovalCheck: true,
                resourceId: $pair['resource_id'],
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

        foreach ($mapping as $mappingData) {
            if ($mappingData['is_internal']) {
                unset($globalFields[$mappingData['permission_field_id']]);
            }
        }

        $this->updateGlobalFieldsAction->execute($virtualUserId, $globalFields);
        $this->rehireIfDeactivated($virtualUserId);

        if ($virtualUserId !== null && $managedPermissionIds !== []) {
            $this->reconcileGrants(
                (int) $virtualUserId,
                $row,
                $triggerClassPatterns,
                $departmentFieldName,
                $managedPermissionIds,
                $fallbackPermissionIds,
            );
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

        $this->rehireIfDeactivated($virtualUserId);

        if ($virtualUserId === null || $managedPermissionIds === []) {
            $stats['skipped']++;

            return;
        }

        $this->reconcileGrants(
            (int) $virtualUserId,
            $row,
            $triggerClassPatterns,
            $departmentFieldName,
            $managedPermissionIds,
            $fallbackPermissionIds,
        );

        $stats['synced']++;
    }

    /**
     * @param array{created: int, updated: int, fired: int, synced: int, skipped: int, errors: int} $stats
     */
    private function processMissingRow(ImportStagingRow $row, array $managedPermissionIds, array &$stats): void
    {
        $virtualUserId = $row->{ImportStagingRow::MATCHED_VIRTUAL_USER_ID};

        if ($virtualUserId !== null && $managedPermissionIds !== []) {
            $currentPairs = $this->loadCurrentManagedPairs((int) $virtualUserId, $managedPermissionIds);
            foreach ($currentPairs as $pair) {
                $this->revokePermissionAction->handle(
                    userId: (int) $virtualUserId,
                    permissionId: $pair['permission_id'],
                    skipTriggers: true,
                    resourceId: $pair['resource_id'],
                );
            }
        }

        if ($virtualUserId !== null) {
            $this->fireVirtualUserAction->handle(userId: (int) $virtualUserId, skipHrTriggers: true);
        }

        $stats['fired']++;
    }

    private function reconcileGrants(
        int $virtualUserId,
        ImportStagingRow $row,
        array $triggerClassPatterns,
        string $departmentFieldName,
        array $managedPermissionIds,
        array $fallbackPermissionIds,
    ): void {
        $shouldHavePairs = $this->resolvePermissionPairsFromRow(
            $row,
            $triggerClassPatterns,
            $departmentFieldName,
            $fallbackPermissionIds
        );
        $shouldKeys = [];
        foreach ($shouldHavePairs as $pair) {
            $shouldKeys[UserAutoGrantPairsCollector::key($pair['permission_id'], $pair['resource_id'])] = $pair;
        }

        $currentPairs = $this->loadCurrentManagedPairs($virtualUserId, $managedPermissionIds);
        $currentKeys = [];
        foreach ($currentPairs as $pair) {
            $currentKeys[UserAutoGrantPairsCollector::key($pair['permission_id'], $pair['resource_id'])] = $pair;
        }

        $toGrant = array_diff_key($shouldKeys, $currentKeys);
        $toRevoke = array_diff_key($currentKeys, $shouldKeys);

        foreach ($toGrant as $pair) {
            $this->grantPermissionAction->handle(
                userId: $virtualUserId,
                permissionId: $pair['permission_id'],
                skipTriggers: true,
                skipApprovalCheck: true,
                resourceId: $pair['resource_id'],
            );
        }

        foreach ($toRevoke as $pair) {
            $this->revokePermissionAction->handle(
                userId: $virtualUserId,
                permissionId: $pair['permission_id'],
                skipTriggers: true,
                resourceId: $pair['resource_id'],
            );
        }
    }

    private function rehireIfDeactivated(?int $virtualUserId): void
    {
        if ($virtualUserId === null) {
            return;
        }

        $user = VirtualUser::query()->find($virtualUserId);
        if ($user === null) {
            return;
        }

        $status = $user->status instanceof VirtualUserStatus
            ? $user->status
            : VirtualUserStatus::tryFrom((string) $user->status);

        if ($status !== VirtualUserStatus::DEACTIVATED) {
            return;
        }

        $this->hireVirtualUserAction->handle(userId: $virtualUserId, skipHrTriggers: true);
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
     * Возвращает пары (permission_id, resource_id|null), которые должны быть у пользователя.
     * Для service-scoped прав resource_id = null. Для resource-scoped — резолвится из каталога
     * permission_resources по (service, kind, external_id=department_id). Если ресурса нет —
     * пара пропускается, в лог пишется warning.
     *
     * @param array<int, string> $triggerClassPatterns
     * @param array<int, int>    $fallbackPermissionIds
     * @return array<int, array{permission_id:int, resource_id:?int}>
     */
    private function resolvePermissionPairsFromRow(
        ImportStagingRow $row,
        array $triggerClassPatterns,
        string $departmentFieldName,
        array $fallbackPermissionIds = []
    ): array {
        $fields = $this->extractRowFields($row);
        $departmentIds = $this->triggerPermissionMatcherService->normalizeDepartmentIds(
            $fields[$departmentFieldName] ?? null
        );

        $matched = $this->triggerPermissionMatcherService
            ->matchByDepartments($departmentIds, $triggerClassPatterns);

        $matchedPermissionIds = $matched->pluck('permission_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
        $matchedPermissionIds = array_values(array_diff($matchedPermissionIds, $fallbackPermissionIds));

        if ($matchedPermissionIds === [] && $fallbackPermissionIds !== []) {
            return $this->pairsForServiceScoped(array_values(array_unique($fallbackPermissionIds)));
        }

        if ($matchedPermissionIds === []) {
            return [];
        }

        return $this->buildPairs($matchedPermissionIds, $matched->all());
    }

    /**
     * @param array<int, int> $permissionIds
     * @return array<int, array{permission_id:int, resource_id:?int}>
     */
    private function pairsForServiceScoped(array $permissionIds): array
    {
        if ($permissionIds === []) {
            return [];
        }

        $permissions = Permission::query()->whereIn('id', $permissionIds)->get()->keyBy('id');
        $pairs = [];
        foreach ($permissionIds as $permissionId) {
            $permission = $permissions[$permissionId] ?? null;
            if ($permission === null) {
                continue;
            }
            $scope = $permission->scope ?? PermissionScope::Service;
            if ($scope === PermissionScope::Resource) {
                Log::warning('Import fallback: skipping resource-scoped permission (no department context)', [
                    'permission_id' => $permissionId,
                ]);
                continue;
            }
            $pairs[] = ['permission_id' => $permissionId, 'resource_id' => null];
        }
        return $pairs;
    }

    /**
     * @param array<int, int> $permissionIds
     * @param array<int, array{permission_id:int, permission_name:string, department_id:string}> $matched
     * @return array<int, array{permission_id:int, resource_id:?int}>
     */
    private function buildPairs(array $permissionIds, array $matched): array
    {
        $permissions = Permission::query()->whereIn('id', $permissionIds)->get()->keyBy('id');

        $resourceNeeded = [];
        foreach ($permissions as $permission) {
            $scope = $permission->scope ?? PermissionScope::Service;
            if ($scope === PermissionScope::Resource && $permission->resource_kind) {
                $resourceNeeded[$permission->service.'|'.$permission->resource_kind] = [
                    'service' => $permission->service,
                    'kind' => $permission->resource_kind,
                ];
            }
        }

        $resourceMap = [];
        foreach ($resourceNeeded as $entry) {
            $externalIds = collect($matched)
                ->where('permission_id', '!=', null)
                ->pluck('department_id')
                ->filter(fn ($id) => $id !== null && $id !== '')
                ->map(fn ($id) => (string) $id)
                ->unique()
                ->values()
                ->all();

            if ($externalIds === []) {
                continue;
            }

            PermissionResource::query()
                ->where('service', $entry['service'])
                ->where('kind', $entry['kind'])
                ->whereIn('external_id', $externalIds)
                ->get()
                ->each(function (PermissionResource $resource) use (&$resourceMap, $entry) {
                    $resourceMap[$entry['service'].'|'.$entry['kind'].'|'.$resource->external_id] = (int) $resource->id;
                });
        }

        $pairs = [];
        $seen = [];
        foreach ($matched as $item) {
            $permissionId = (int) $item['permission_id'];
            $departmentExternalId = (string) $item['department_id'];
            $permission = $permissions[$permissionId] ?? null;
            if ($permission === null) {
                continue;
            }
            $scope = $permission->scope ?? PermissionScope::Service;

            if ($scope !== PermissionScope::Resource) {
                $key = $permissionId.'|';
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $pairs[] = ['permission_id' => $permissionId, 'resource_id' => null];
                continue;
            }

            if (!$permission->resource_kind) {
                Log::warning('Import: resource-scoped permission has no resource_kind, skipping', [
                    'permission_id' => $permissionId,
                ]);
                continue;
            }

            $resourceKey = $permission->service.'|'.$permission->resource_kind.'|'.$departmentExternalId;
            $resourceId = $resourceMap[$resourceKey] ?? null;
            if ($resourceId === null) {
                Log::warning('Import: resource not found in catalog, skipping pair', [
                    'permission_id' => $permissionId,
                    'service' => $permission->service,
                    'kind' => $permission->resource_kind,
                    'external_id' => $departmentExternalId,
                ]);
                continue;
            }

            $key = $permissionId.'|'.$resourceId;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $pairs[] = ['permission_id' => $permissionId, 'resource_id' => $resourceId];
        }

        return $pairs;
    }

    /**
     * @param array<int, array{permission_id:int, resource_id:?int}> $pairs
     * @return array<int, int>
     */
    private function permissionIdsFromPairs(array $pairs): array
    {
        return array_values(array_unique(array_map(static fn ($p) => $p['permission_id'], $pairs)));
    }

    /**
     * @param array<int, int> $permissionIds
     * @return array<string, true>
     */
    private function existingPairKeys(int $userId, array $permissionIds): array
    {
        if ($permissionIds === []) {
            return [];
        }
        $keys = [];
        GrantedPermission::query()
            ->where(GrantedPermission::VIRTUAL_USER_ID, $userId)
            ->whereIn(GrantedPermission::PERMISSION_ID, $permissionIds)
            ->get(['permission_id', 'resource_id'])
            ->each(function ($grant) use (&$keys) {
                $resourceId = $grant->resource_id !== null ? (int) $grant->resource_id : null;
                $keys[UserAutoGrantPairsCollector::key((int) $grant->permission_id, $resourceId)] = true;
            });
        return $keys;
    }

    /**
     * @param array<int, int> $managedPermissionIds
     * @return array<int, array{permission_id:int, resource_id:?int}>
     */
    private function loadCurrentManagedPairs(int $userId, array $managedPermissionIds): array
    {
        if ($managedPermissionIds === []) {
            return [];
        }
        return GrantedPermission::query()
            ->where(GrantedPermission::VIRTUAL_USER_ID, $userId)
            ->whereIn(GrantedPermission::PERMISSION_ID, $managedPermissionIds)
            ->where('enabled', true)
            ->get(['permission_id', 'resource_id'])
            ->map(static fn ($g) => [
                'permission_id' => (int) $g->permission_id,
                'resource_id' => $g->resource_id !== null ? (int) $g->resource_id : null,
            ])
            ->all();
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
