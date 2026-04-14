<?php

namespace ArcheeNic\PermissionRegistry\Livewire;

use ArcheeNic\PermissionRegistry\Actions\ApproveImportRowsAction;
use ArcheeNic\PermissionRegistry\Actions\CleanupImportRunAction;
use ArcheeNic\PermissionRegistry\Actions\ExecuteApprovedImportAction;
use ArcheeNic\PermissionRegistry\Actions\FetchImportAction;
use ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\ImportExecutionLog;
use ArcheeNic\PermissionRegistry\Models\ImportFieldMapping;
use ArcheeNic\PermissionRegistry\Models\ImportStagingRow;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\PermissionImport;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use ArcheeNic\PermissionRegistry\Services\ImportDiscoveryService;
use ArcheeNic\PermissionRegistry\Services\ImportFieldMappingService;
use ArcheeNic\PermissionRegistry\Services\ImportTriggerConfigResolver;
use ArcheeNic\PermissionRegistry\Services\TriggerPermissionMatcherService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ImportManager extends Component
{
    use WithPagination;

    public string $step = 'list';

    public ?string $currentRunId = null;

    public ?int $currentImportId = null;

    public array $selectedRows = [];

    public ?string $flashMessage = null;

    public ?string $flashError = null;

    public array $executionResult = [];

    public array $fieldMapping = [];

    public ?int $internalFieldId = null;

    public array $statusFilters = [];

    public ?int $grantPermissionFilterId = null;

    public ?int $revokePermissionFilterId = null;

    private const ROWS_PER_PAGE = 100;

    public function mount(): void
    {
        $this->syncDiscoveredImports();
    }

    public function syncDiscoveredImports(): void
    {
        $discovered = app(ImportDiscoveryService::class)->discover();

        foreach ($discovered as $import) {
            PermissionImport::query()->updateOrCreate(
                [PermissionImport::CLASS_NAME => $import['class_name']],
                [
                    PermissionImport::NAME => $import['name'],
                    PermissionImport::DESCRIPTION => $import['description'] ?? null,
                    PermissionImport::IS_ACTIVE => true,
                ],
            );
        }
    }

    public function openSettings(int $importId): void
    {
        $this->currentImportId = $importId;
        $this->loadCurrentMapping();
        $this->step = 'settings';
    }

    public function saveMapping(): void
    {
        if (! $this->currentImportId) {
            return;
        }

        try {
            DB::transaction(function () {
                ImportFieldMapping::query()
                    ->where(ImportFieldMapping::PERMISSION_IMPORT_ID, $this->currentImportId)
                    ->delete();

                foreach ($this->fieldMapping as $importFieldName => $permissionFieldId) {
                    if (empty($permissionFieldId)) {
                        continue;
                    }

                    ImportFieldMapping::query()->create([
                        ImportFieldMapping::PERMISSION_IMPORT_ID => $this->currentImportId,
                        ImportFieldMapping::IMPORT_FIELD_NAME => $importFieldName,
                        ImportFieldMapping::PERMISSION_FIELD_ID => (int) $permissionFieldId,
                        ImportFieldMapping::IS_INTERNAL => false,
                    ]);
                }

                if ($this->internalFieldId) {
                    $emailFieldName = $this->resolveEmailImportFieldName();
                    if ($emailFieldName) {
                        ImportFieldMapping::query()->updateOrCreate(
                            [
                                ImportFieldMapping::PERMISSION_IMPORT_ID => $this->currentImportId,
                                ImportFieldMapping::IMPORT_FIELD_NAME => $emailFieldName,
                            ],
                            [
                                ImportFieldMapping::PERMISSION_FIELD_ID => (int) $this->internalFieldId,
                                ImportFieldMapping::IS_INTERNAL => true,
                            ],
                        );
                    }
                }
            });

            app(ImportFieldMappingService::class)->clearCache($this->currentImportId);

            $this->flashMessage = __('permission-registry::messages.import.mapping_saved');
            $this->step = 'list';
        } catch (\Throwable $e) {
            $this->flashError = $e->getMessage();
        }
    }

    public function startImport(int $importId): void
    {
        $this->resetState();

        $hasMappings = ImportFieldMapping::query()
            ->where(ImportFieldMapping::PERMISSION_IMPORT_ID, $importId)
            ->exists();

        if (! $hasMappings) {
            $this->flashError = __('permission-registry::messages.import.no_mapping');

            return;
        }

        try {
            $this->currentImportId = $importId;
            $this->currentRunId = app(FetchImportAction::class)->handle($importId);
            $this->step = 'staging';
        } catch (\Throwable $e) {
            $this->flashError = $e->getMessage();
        }
    }

    public function toggleRow(int $rowId): void
    {
        if (in_array($rowId, $this->selectedRows, true)) {
            $this->selectedRows = array_values(array_diff($this->selectedRows, [$rowId]));
        } else {
            $this->selectedRows[] = $rowId;
        }
    }

    public function selectAll(): void
    {
        if (! $this->currentRunId) {
            return;
        }

        $query = ImportStagingRow::query()
            ->where(ImportStagingRow::IMPORT_RUN_ID, $this->currentRunId);

        if ($this->statusFilters !== []) {
            $query->whereIn(ImportStagingRow::MATCH_STATUS, $this->statusFilters);
        }

        $this->selectedRows = $query
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    public function selectAllOnPage(): void
    {
        $rows = $this->getStagingRows();
        $items = $rows instanceof LengthAwarePaginator
            ? collect($rows->items())
            : collect($rows);

        $pageIds = $items->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();
        $this->selectedRows = array_values(array_unique(array_merge($this->selectedRows, $pageIds)));
    }

    public function deselectAllOnPage(): void
    {
        $rows = $this->getStagingRows();
        $items = $rows instanceof LengthAwarePaginator
            ? collect($rows->items())
            : collect($rows);

        $pageIds = $items->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();
        $this->selectedRows = array_values(array_diff($this->selectedRows, $pageIds));
    }

    public function deselectAll(): void
    {
        $this->selectedRows = [];
    }

    public function selectByStatus(string $status): void
    {
        $validStatus = ImportMatchStatus::tryFrom($status);
        if ($validStatus === null || ! $this->currentRunId) {
            return;
        }

        $statusIds = ImportStagingRow::query()
            ->where(ImportStagingRow::IMPORT_RUN_ID, $this->currentRunId)
            ->where(ImportStagingRow::MATCH_STATUS, $validStatus)
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $this->selectedRows = array_values(array_unique(array_merge($this->selectedRows, $statusIds)));
    }

    public function approveAndExecute(): void
    {
        if (! $this->currentRunId || empty($this->selectedRows)) {
            return;
        }

        $this->step = 'executing';

        try {
            app(ApproveImportRowsAction::class)->handle($this->currentRunId, $this->selectedRows);
            $this->executionResult = app(ExecuteApprovedImportAction::class)->handle($this->currentRunId);
            $this->step = 'done';
        } catch (\Throwable $e) {
            $this->flashError = $e->getMessage();
            $this->step = 'staging';
        }
    }

    public function cancelImport(): void
    {
        if ($this->currentRunId) {
            try {
                app(CleanupImportRunAction::class)->handle($this->currentRunId);
            } catch (\Throwable) {
                // cleanup best-effort
            }
        }

        $this->resetState();
    }

    public function toggleStatusFilter(string $status): void
    {
        if (ImportMatchStatus::tryFrom($status) === null) {
            return;
        }

        if (in_array($status, $this->statusFilters, true)) {
            $this->statusFilters = array_values(array_diff($this->statusFilters, [$status]));
        } else {
            $this->statusFilters[] = $status;
        }

        $this->resetPage();
    }

    public function clearStatusFilter(): void
    {
        $this->statusFilters = [];
        $this->resetPage();
    }

    public function clearPermissionFilters(): void
    {
        $this->grantPermissionFilterId = null;
        $this->revokePermissionFilterId = null;
        $this->resetPage();
    }

    public function updatedStatusFilters(): void
    {
        $this->resetPage();
    }

    public function updatedGrantPermissionFilterId(): void
    {
        $this->resetPage();
    }

    public function updatedRevokePermissionFilterId(): void
    {
        $this->resetPage();
    }

    public function viewRun(string $importRunId): void
    {
        $this->resetState();
        $this->currentRunId = $importRunId;

        $log = ImportExecutionLog::query()
            ->where(ImportExecutionLog::IMPORT_RUN_ID, $importRunId)
            ->first();

        if ($log) {
            $this->currentImportId = $log->{ImportExecutionLog::PERMISSION_IMPORT_ID};
        }

        $this->step = 'history_view';
    }

    public function backToList(): void
    {
        $this->resetState();
    }

    public function getImportsProperty()
    {
        return PermissionImport::query()
            ->where(PermissionImport::IS_ACTIVE, true)
            ->orderBy(PermissionImport::NAME)
            ->get();
    }

    public function getExecutionLogsProperty()
    {
        return ImportExecutionLog::query()
            ->with('permissionImport')
            ->orderByDesc(ImportExecutionLog::CREATED_AT)
            ->limit(20)
            ->get();
    }

    public function getStagingRowsProperty()
    {
        return $this->getStagingRows();
    }

    public function getManagedPermissionsProperty()
    {
        if (! $this->currentImportId) {
            return collect();
        }

        [$patterns] = $this->resolveMatcherConfig();
        $managedIds = app(TriggerPermissionMatcherService::class)->getAllManagedPermissionIds($patterns);
        if ($managedIds === []) {
            return collect();
        }

        return \ArcheeNic\PermissionRegistry\Models\Permission::query()
            ->whereIn('id', $managedIds)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getStagingStatsProperty(): array
    {
        $rows = $this->getStagingRows();
        $items = $rows instanceof LengthAwarePaginator
            ? collect($rows->items())
            : collect($rows);

        return [
            'total' => $items->count(),
            'new' => $items->where(ImportStagingRow::MATCH_STATUS, ImportMatchStatus::NEW)->count(),
            'changed' => $items->where(ImportStagingRow::MATCH_STATUS, ImportMatchStatus::CHANGED)->count(),
            'exists' => $items->where(ImportStagingRow::MATCH_STATUS, ImportMatchStatus::EXISTS)->count(),
            'missing' => $items->where(ImportStagingRow::MATCH_STATUS, ImportMatchStatus::MISSING)->count(),
        ];
    }

    public function getResolvedPermissionNameProperty(): ?string
    {
        $names = $this->resolvedPermissionNames;

        if ($names === []) {
            return null;
        }

        return implode(', ', $names);
    }

    /**
     * @return array<int, string>
     */
    public function getResolvedPermissionNamesProperty(): array
    {
        if (! $this->currentImportId) {
            return [];
        }

        [$patterns] = $this->resolveMatcherConfig();
        $managedIds = app(TriggerPermissionMatcherService::class)->getAllManagedPermissionIds($patterns);
        if ($managedIds === []) {
            return [];
        }

        return \ArcheeNic\PermissionRegistry\Models\Permission::query()
            ->whereIn('id', $managedIds)
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();
    }

    public function getRowActionsProperty(): array
    {
        $rows = $this->getFilteredRows();
        $rowsCollection = $rows instanceof LengthAwarePaginator
            ? collect($rows->items())
            : collect($rows);
        $actions = [];
        $matcher = app(TriggerPermissionMatcherService::class);
        [$patterns, $departmentFieldName] = $this->resolveMatcherConfig();
        $managedPermissionIds = $matcher->getAllManagedPermissionIds($patterns);

        $changedRows = $rowsCollection->filter(function ($row) {
            $status = $row->match_status instanceof ImportMatchStatus
                ? $row->match_status
                : ImportMatchStatus::tryFrom($row->match_status);

            return $status === ImportMatchStatus::CHANGED;
        });

        $diffs = $this->computeFieldDiffs($changedRows);

        foreach ($rowsCollection as $row) {
            $fields = is_array($row->fields) ? $row->fields : [];
            $departmentIds = $matcher->normalizeDepartmentIds($fields[$departmentFieldName] ?? null);
            $matchedPermissions = $matcher->matchByDepartments($departmentIds, $patterns);
            $matchedPermissionIds = $matchedPermissions
                ->pluck('permission_id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->unique()
                ->values()
                ->all();

            $status = $row->match_status instanceof ImportMatchStatus
                ? $row->match_status
                : ImportMatchStatus::tryFrom($row->match_status);

            $actions[$row->id] = match ($status) {
                ImportMatchStatus::NEW => $this->buildNewAction($matchedPermissions),
                ImportMatchStatus::CHANGED => $this->buildChangedAction(
                    $diffs[$row->id] ?? [],
                    $this->buildPermissionDiff(
                        $row->{ImportStagingRow::MATCHED_VIRTUAL_USER_ID},
                        $matchedPermissionIds,
                        $managedPermissionIds
                    )
                ),
                ImportMatchStatus::EXISTS => ['items' => [
                    ['icon' => 'check', 'text' => __('permission-registry::messages.import.action_detail_no_changes')],
                ]],
                ImportMatchStatus::MISSING => $this->buildMissingAction(
                    (int) ($row->{ImportStagingRow::MATCHED_VIRTUAL_USER_ID} ?? 0),
                    $managedPermissionIds
                ),
                default => ['items' => []],
            };
        }

        return $actions;
    }

    public function getAllStatsProperty(): array
    {
        if (! $this->currentRunId) {
            return ['total' => 0, 'new' => 0, 'changed' => 0, 'exists' => 0, 'missing' => 0];
        }

        return ImportStagingRow::query()
            ->where(ImportStagingRow::IMPORT_RUN_ID, $this->currentRunId)
            ->selectRaw("
                count(*) as total,
                count(*) filter (where match_status = 'new') as new,
                count(*) filter (where match_status = 'changed') as changed,
                count(*) filter (where match_status = 'exists') as \"exists\",
                count(*) filter (where match_status = 'missing') as missing
            ")
            ->first()
            ?->toArray() ?? ['total' => 0, 'new' => 0, 'changed' => 0, 'exists' => 0, 'missing' => 0];
    }

    public function getGlobalFieldsProperty()
    {
        return PermissionField::query()
            ->where(PermissionField::IS_GLOBAL, true)
            ->orderBy(PermissionField::NAME)
            ->get();
    }

    public function getRequiredFieldsProperty(): array
    {
        if (! $this->currentImportId) {
            return [];
        }

        $import = PermissionImport::query()->find($this->currentImportId);
        if (! $import) {
            return [];
        }

        $metadata = app(ImportDiscoveryService::class)
            ->getImportMetadata($import->{PermissionImport::CLASS_NAME});

        return $metadata['required_fields'] ?? [];
    }

    /**
     * @return array<string, string> import_field_name => permission_field.name
     */
    public function getFieldColumnsProperty(): array
    {
        if (! $this->currentImportId) {
            return [];
        }

        $mappings = ImportFieldMapping::query()
            ->where(ImportFieldMapping::PERMISSION_IMPORT_ID, $this->currentImportId)
            ->with('permissionField')
            ->orderBy('id')
            ->get();

        $columns = [];
        foreach ($mappings as $mapping) {
            $columns[$mapping->{ImportFieldMapping::IMPORT_FIELD_NAME}] =
                $mapping->permissionField?->name ?? $mapping->{ImportFieldMapping::IMPORT_FIELD_NAME};
        }

        return $columns;
    }

    public function render()
    {
        $filteredRows = $this->getFilteredRows();

        return view('permission-registry::livewire.import-manager', [
            'imports' => $this->imports,
            'executionLogs' => $this->executionLogs,
            'stagingRows' => $filteredRows,
            'stagingStats' => $this->allStats,
            'globalFields' => $this->globalFields,
            'requiredFields' => $this->requiredFields,
            'rowActions' => $this->rowActions,
            'resolvedPermissionName' => $this->resolvedPermissionName,
            'managedPermissions' => $this->managedPermissions,
            'fieldColumns' => $this->fieldColumns,
        ]);
    }

    private function getFilteredRows()
    {
        if (! $this->currentRunId) {
            return $this->emptyPaginator();
        }

        $query = ImportStagingRow::query()
            ->where(ImportStagingRow::IMPORT_RUN_ID, $this->currentRunId)
            ->with('matchedVirtualUser')
            ->orderBy(ImportStagingRow::MATCH_STATUS);

        if ($this->statusFilters !== []) {
            $query->whereIn(ImportStagingRow::MATCH_STATUS, $this->statusFilters);
        }

        if ($this->grantPermissionFilterId || $this->revokePermissionFilterId) {
            $matchingIds = $this->resolveActionFilteredRowIds((clone $query)->get());
            if ($matchingIds === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id', $matchingIds);
            }
        }

        return $query->paginate(self::ROWS_PER_PAGE);
    }

    private function getStagingRows()
    {
        return $this->getFilteredRows();
    }

    private function loadCurrentMapping(): void
    {
        $this->fieldMapping = [];
        $this->internalFieldId = null;

        if (! $this->currentImportId) {
            return;
        }

        $mappings = ImportFieldMapping::query()
            ->where(ImportFieldMapping::PERMISSION_IMPORT_ID, $this->currentImportId)
            ->get();

        foreach ($mappings as $mapping) {
            if ($mapping->{ImportFieldMapping::IS_INTERNAL}) {
                $this->internalFieldId = $mapping->{ImportFieldMapping::PERMISSION_FIELD_ID};
            }
            $this->fieldMapping[$mapping->{ImportFieldMapping::IMPORT_FIELD_NAME}] = (string) $mapping->{ImportFieldMapping::PERMISSION_FIELD_ID};
        }
    }

    private function resolveEmailImportFieldName(): ?string
    {
        if (! $this->currentImportId) {
            return null;
        }

        $import = PermissionImport::query()->find($this->currentImportId);
        if (! $import) {
            return null;
        }

        $metadata = app(ImportDiscoveryService::class)
            ->getImportMetadata($import->{PermissionImport::CLASS_NAME});

        $fields = $metadata['required_fields'] ?? [];

        foreach ($fields as $field) {
            if (str_contains(mb_strtolower($field['name']), 'email')) {
                return $field['name'];
            }
        }

        return $fields[0]['name'] ?? null;
    }

    private function buildNewAction($matchedPermissions): array
    {
        $items = [
            ['icon' => 'user-plus', 'text' => __('permission-registry::messages.import.action_detail_create_user')],
            ['icon' => 'briefcase', 'text' => __('permission-registry::messages.import.action_detail_hire')],
        ];

        $permissionNames = $matchedPermissions
            ->pluck('permission_name')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($permissionNames !== []) {
            $items[] = [
                'icon' => 'key',
                'text' => __('permission-registry::messages.import.action_detail_grant_list', [
                    'names' => implode(', ', $permissionNames),
                ]),
            ];
        }

        return ['items' => $items];
    }

    private function buildChangedAction(array $diffs, array $permissionDiff): array
    {
        $items = [];

        foreach ($diffs as $diff) {
            $items[] = [
                'icon' => 'pencil',
                'text' => "{$diff['field']}: {$diff['old']} → {$diff['new']}",
            ];
        }

        foreach ($permissionDiff['added'] as $name) {
            $items[] = [
                'icon' => 'key',
                'text' => __('permission-registry::messages.import.action_detail_perm_added', ['name' => $name]),
            ];
        }

        foreach ($permissionDiff['removed'] as $name) {
            $items[] = [
                'icon' => 'key-slash',
                'text' => __('permission-registry::messages.import.action_detail_perm_removed', ['name' => $name]),
            ];
        }

        if (empty($items)) {
            $items[] = ['icon' => 'pencil', 'text' => __('permission-registry::messages.import.action_detail_update_fields')];
        }

        return ['items' => $items];
    }

    private function buildMissingAction(int $virtualUserId, array $managedPermissionIds): array
    {
        $items = [];

        if ($virtualUserId > 0 && $managedPermissionIds !== []) {
            $permissionNames = \ArcheeNic\PermissionRegistry\Models\Permission::query()
                ->whereIn('id', GrantedPermission::query()
                    ->where(GrantedPermission::VIRTUAL_USER_ID, $virtualUserId)
                    ->whereIn(GrantedPermission::PERMISSION_ID, $managedPermissionIds)
                    ->pluck(GrantedPermission::PERMISSION_ID)
                    ->all())
                ->orderBy('name')
                ->pluck('name')
                ->values()
                ->all();

            if ($permissionNames !== []) {
                $items[] = [
                    'icon' => 'key-slash',
                    'text' => __('permission-registry::messages.import.action_detail_revoke_list', [
                        'names' => implode(', ', $permissionNames),
                    ]),
                ];
            }
        }

        $items[] = ['icon' => 'user-minus', 'text' => __('permission-registry::messages.import.action_detail_fire')];

        return ['items' => $items];
    }

    private function computeFieldDiffs($changedRows): array
    {
        if ($changedRows->isEmpty() || ! $this->currentImportId) {
            return [];
        }

        $mapping = app(ImportFieldMappingService::class)->getMapping($this->currentImportId);

        $emailFieldId = ImportFieldMapping::query()
            ->where(ImportFieldMapping::PERMISSION_IMPORT_ID, $this->currentImportId)
            ->where(ImportFieldMapping::IS_INTERNAL, true)
            ->value(ImportFieldMapping::PERMISSION_FIELD_ID);

        $fieldNames = PermissionField::query()
            ->whereIn('id', collect($mapping)->pluck('permission_field_id'))
            ->pluck('name', 'id')
            ->toArray();

        $diffs = [];

        foreach ($changedRows as $row) {
            $vuId = $row->{ImportStagingRow::MATCHED_VIRTUAL_USER_ID};
            if (! $vuId) {
                continue;
            }

            $existingValues = VirtualUserFieldValue::query()
                ->where(VirtualUserFieldValue::VIRTUAL_USER_ID, $vuId)
                ->pluck('value', 'permission_field_id')
                ->toArray();

            $fields = is_array($row->fields) ? $row->fields : [];
            $rowDiffs = [];

            foreach ($mapping as $importFieldName => $mappingData) {
                $fieldId = $mappingData['permission_field_id'];

                if ($fieldId === $emailFieldId) {
                    continue;
                }

                $importedValue = (string) ($fields[$importFieldName] ?? '');
                $existingValue = (string) ($existingValues[$fieldId] ?? '');

                if ($importedValue !== $existingValue) {
                    $rowDiffs[] = [
                        'field' => $fieldNames[$fieldId] ?? $importFieldName,
                        'old' => $existingValue ?: '—',
                        'new' => $importedValue ?: '—',
                    ];
                }
            }

            $diffs[$row->id] = $rowDiffs;
        }

        return $diffs;
    }

    private function resetState(): void
    {
        $this->step = 'list';
        $this->currentRunId = null;
        $this->currentImportId = null;
        $this->selectedRows = [];
        $this->flashMessage = null;
        $this->flashError = null;
        $this->executionResult = [];
        $this->fieldMapping = [];
        $this->internalFieldId = null;
        $this->statusFilters = [];
        $this->grantPermissionFilterId = null;
        $this->revokePermissionFilterId = null;
        $this->resetPage();
    }

    /**
     * @param  Collection<int, ImportStagingRow>  $rows
     * @return array<int, int>
     */
    private function resolveActionFilteredRowIds(Collection $rows): array
    {
        $matcher = app(TriggerPermissionMatcherService::class);
        [$patterns, $departmentFieldName] = $this->resolveMatcherConfig();
        $managedPermissionIds = $matcher->getAllManagedPermissionIds($patterns);
        $grantFilterId = $this->grantPermissionFilterId ? (int) $this->grantPermissionFilterId : null;
        $revokeFilterId = $this->revokePermissionFilterId ? (int) $this->revokePermissionFilterId : null;

        $matchedUserIds = $rows
            ->pluck(ImportStagingRow::MATCHED_VIRTUAL_USER_ID)
            ->filter()
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $currentPermissionMap = GrantedPermission::query()
            ->whereIn(GrantedPermission::VIRTUAL_USER_ID, $matchedUserIds->all())
            ->whereIn(GrantedPermission::PERMISSION_ID, $managedPermissionIds)
            ->get([GrantedPermission::VIRTUAL_USER_ID, GrantedPermission::PERMISSION_ID])
            ->groupBy(GrantedPermission::VIRTUAL_USER_ID)
            ->map(fn (Collection $items): array => $items
                ->pluck(GrantedPermission::PERMISSION_ID)
                ->map(static fn (mixed $id): int => (int) $id)
                ->values()
                ->all())
            ->toArray();

        $ids = [];

        foreach ($rows as $row) {
            $fields = is_array($row->fields) ? $row->fields : [];
            $departmentIds = $matcher->normalizeDepartmentIds($fields[$departmentFieldName] ?? null);
            $matchedPermissionIds = $matcher->matchByDepartments($departmentIds, $patterns)
                ->pluck('permission_id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->unique()
                ->values()
                ->all();

            $status = $row->match_status instanceof ImportMatchStatus
                ? $row->match_status
                : ImportMatchStatus::tryFrom($row->match_status);

            $granted = [];
            $revoked = [];

            if ($status === ImportMatchStatus::NEW) {
                $granted = $matchedPermissionIds;
            } elseif ($status === ImportMatchStatus::CHANGED) {
                $userId = (int) ($row->{ImportStagingRow::MATCHED_VIRTUAL_USER_ID} ?? 0);
                $currentIds = $currentPermissionMap[$userId] ?? [];
                $granted = array_values(array_diff($matchedPermissionIds, $currentIds));
                $revoked = array_values(array_diff($currentIds, $matchedPermissionIds));
            } elseif ($status === ImportMatchStatus::MISSING) {
                $userId = (int) ($row->{ImportStagingRow::MATCHED_VIRTUAL_USER_ID} ?? 0);
                $revoked = $currentPermissionMap[$userId] ?? [];
            }

            $grantOk = ! $grantFilterId || in_array($grantFilterId, $granted, true);
            $revokeOk = ! $revokeFilterId || in_array($revokeFilterId, $revoked, true);

            if ($grantOk && $revokeOk) {
                $ids[] = (int) $row->id;
            }
        }

        return $ids;
    }

    private function emptyPaginator(): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, self::ROWS_PER_PAGE, 1);
    }

    /**
     * @return array{added: array<int, string>, removed: array<int, string>}
     */
    private function buildPermissionDiff(?int $virtualUserId, array $shouldHaveIds, array $managedPermissionIds): array
    {
        if (! $virtualUserId || $managedPermissionIds === []) {
            return ['added' => [], 'removed' => []];
        }

        $currentIds = GrantedPermission::query()
            ->where(GrantedPermission::VIRTUAL_USER_ID, $virtualUserId)
            ->whereIn(GrantedPermission::PERMISSION_ID, $managedPermissionIds)
            ->pluck(GrantedPermission::PERMISSION_ID)
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $toAdd = array_values(array_diff($shouldHaveIds, $currentIds));
        $toRemove = array_values(array_diff($currentIds, $shouldHaveIds));

        return [
            'added' => \ArcheeNic\PermissionRegistry\Models\Permission::query()
                ->whereIn('id', $toAdd)
                ->orderBy('name')
                ->pluck('name')
                ->values()
                ->all(),
            'removed' => \ArcheeNic\PermissionRegistry\Models\Permission::query()
                ->whereIn('id', $toRemove)
                ->orderBy('name')
                ->pluck('name')
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{0: array<int, string>, 1: string}
     */
    private function resolveMatcherConfig(): array
    {
        if (! $this->currentImportId) {
            return app(ImportTriggerConfigResolver::class)->resolve(null);
        }

        $import = PermissionImport::query()->find($this->currentImportId);

        return app(ImportTriggerConfigResolver::class)->resolve($import);
    }
}
