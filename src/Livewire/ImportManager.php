<?php

namespace ArcheeNic\PermissionRegistry\Livewire;

use ArcheeNic\PermissionRegistry\Actions\ApproveImportRowsAction;
use ArcheeNic\PermissionRegistry\Actions\CleanupImportRunAction;
use ArcheeNic\PermissionRegistry\Actions\ExecuteApprovedImportAction;
use ArcheeNic\PermissionRegistry\Actions\FetchImportAction;
use ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus;
use ArcheeNic\PermissionRegistry\Models\ImportExecutionLog;
use ArcheeNic\PermissionRegistry\Models\ImportStagingRow;
use ArcheeNic\PermissionRegistry\Models\PermissionImport;
use ArcheeNic\PermissionRegistry\Services\ImportDiscoveryService;
use Livewire\Component;

class ImportManager extends Component
{
    public string $step = 'list';

    public ?string $currentRunId = null;

    public ?int $currentImportId = null;

    public array $selectedRows = [];

    public ?string $flashMessage = null;

    public ?string $flashError = null;

    public array $executionResult = [];

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

    public function startImport(int $importId): void
    {
        $this->resetState();

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
        $this->selectedRows = $this->getStagingRows()
            ->pluck('id')
            ->toArray();
    }

    public function deselectAll(): void
    {
        $this->selectedRows = [];
    }

    public function selectByStatus(string $status): void
    {
        $validStatus = ImportMatchStatus::tryFrom($status);
        if ($validStatus === null) {
            return;
        }

        $this->selectedRows = $this->getStagingRows()
            ->where(ImportStagingRow::MATCH_STATUS, $validStatus)
            ->pluck('id')
            ->toArray();
    }

    public function approveAndExecute(): void
    {
        if (!$this->currentRunId || empty($this->selectedRows)) {
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

    public function getStagingStatsProperty(): array
    {
        $rows = $this->getStagingRows();

        return [
            'total' => $rows->count(),
            'new' => $rows->where(ImportStagingRow::MATCH_STATUS, ImportMatchStatus::NEW)->count(),
            'changed' => $rows->where(ImportStagingRow::MATCH_STATUS, ImportMatchStatus::CHANGED)->count(),
            'exists' => $rows->where(ImportStagingRow::MATCH_STATUS, ImportMatchStatus::EXISTS)->count(),
            'missing' => $rows->where(ImportStagingRow::MATCH_STATUS, ImportMatchStatus::MISSING)->count(),
        ];
    }

    public function render()
    {
        return view('permission-registry::livewire.import-manager', [
            'imports' => $this->imports,
            'executionLogs' => $this->executionLogs,
            'stagingRows' => $this->stagingRows,
            'stagingStats' => $this->stagingStats,
        ]);
    }

    private function getStagingRows()
    {
        if (!$this->currentRunId) {
            return collect();
        }

        return ImportStagingRow::query()
            ->where(ImportStagingRow::IMPORT_RUN_ID, $this->currentRunId)
            ->with('matchedVirtualUser')
            ->orderBy(ImportStagingRow::MATCH_STATUS)
            ->get();
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
    }
}
