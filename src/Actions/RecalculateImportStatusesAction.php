<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\ImportStagingRow;
use ArcheeNic\PermissionRegistry\Models\PermissionImport;
use ArcheeNic\PermissionRegistry\Services\ImportTriggerConfigResolver;
use ArcheeNic\PermissionRegistry\Services\TriggerPermissionMatcherService;

class RecalculateImportStatusesAction
{
    public function __construct(
        private ImportTriggerConfigResolver $triggerConfigResolver,
        private TriggerPermissionMatcherService $matcher,
    ) {}

    public function handle(string $importRunId, int $permissionImportId): void
    {
        $import = PermissionImport::query()->find($permissionImportId);
        [$patterns, $departmentFieldName, $fallbackTriggerClass] = $this->triggerConfigResolver->resolve($import);
        $managedPermissionIds = $this->matcher->getAllManagedPermissionIds($patterns);
        $fallbackPermissionIds = $this->matcher->getFallbackPermissionIds($fallbackTriggerClass);
        $managedPermissionIds = array_values(array_unique(array_merge($managedPermissionIds, $fallbackPermissionIds)));

        if ($managedPermissionIds === []) {
            return;
        }

        $existsRows = ImportStagingRow::query()
            ->where(ImportStagingRow::IMPORT_RUN_ID, $importRunId)
            ->where(ImportStagingRow::MATCH_STATUS, ImportMatchStatus::EXISTS->value)
            ->whereNotNull(ImportStagingRow::MATCHED_VIRTUAL_USER_ID)
            ->get();

        if ($existsRows->isEmpty()) {
            return;
        }

        $userIds = $existsRows
            ->pluck(ImportStagingRow::MATCHED_VIRTUAL_USER_ID)
            ->filter()
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $currentPermissionMap = GrantedPermission::query()
            ->whereIn(GrantedPermission::VIRTUAL_USER_ID, $userIds->all())
            ->whereIn(GrantedPermission::PERMISSION_ID, $managedPermissionIds)
            ->get([GrantedPermission::VIRTUAL_USER_ID, GrantedPermission::PERMISSION_ID])
            ->groupBy(GrantedPermission::VIRTUAL_USER_ID)
            ->map(fn ($items) => $items
                ->pluck(GrantedPermission::PERMISSION_ID)
                ->map(static fn (mixed $id): int => (int) $id)
                ->values()
                ->all())
            ->toArray();

        $rowIdsToUpgrade = [];

        foreach ($existsRows as $row) {
            $fields = is_array($row->fields) ? $row->fields : [];
            $departmentIds = $this->matcher->normalizeDepartmentIds($fields[$departmentFieldName] ?? null);
            $shouldHaveIds = $this->matcher->matchByDepartments($departmentIds, $patterns)
                ->pluck('permission_id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->unique()
                ->values()
                ->all();

            $shouldHaveIds = array_values(array_diff($shouldHaveIds, $fallbackPermissionIds));

            if ($shouldHaveIds === []) {
                $shouldHaveIds = $fallbackPermissionIds;
            }

            $userId = (int) $row->{ImportStagingRow::MATCHED_VIRTUAL_USER_ID};
            $currentIds = $currentPermissionMap[$userId] ?? [];

            $toAdd = array_diff($shouldHaveIds, $currentIds);
            $toRemove = array_diff($currentIds, $shouldHaveIds);

            if ($toAdd !== [] || $toRemove !== []) {
                $rowIdsToUpgrade[] = $row->id;
            }
        }

        if ($rowIdsToUpgrade !== []) {
            ImportStagingRow::query()
                ->whereIn('id', $rowIdsToUpgrade)
                ->update([ImportStagingRow::MATCH_STATUS => ImportMatchStatus::CHANGED->value]);
        }
    }
}
