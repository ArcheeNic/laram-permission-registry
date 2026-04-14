<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Livewire;

use ArcheeNic\PermissionRegistry\Livewire\ImportManager;
use ArcheeNic\PermissionRegistry\Models\ImportStagingRow;
use ArcheeNic\PermissionRegistry\Models\PermissionImport;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Str;

class ImportManagerSelectionTest extends TestCase
{
    private string $runId;

    private int $importId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->runId = (string) Str::uuid();

        $import = PermissionImport::query()->create([
            PermissionImport::NAME => 'Test Import',
            PermissionImport::CLASS_NAME => 'App\\Imports\\TestImport',
            PermissionImport::IS_ACTIVE => true,
        ]);

        $this->importId = $import->id;

        $statuses = array_merge(
            array_fill(0, 3, 'new'),
            array_fill(0, 2, 'changed'),
            array_fill(0, 1, 'exists'),
            array_fill(0, 1, 'missing'),
        );

        foreach ($statuses as $status) {
            ImportStagingRow::query()->create([
                ImportStagingRow::IMPORT_RUN_ID => $this->runId,
                ImportStagingRow::PERMISSION_IMPORT_ID => $import->id,
                ImportStagingRow::EXTERNAL_ID => Str::uuid(),
                ImportStagingRow::FIELDS => ['email' => fake()->email()],
                ImportStagingRow::MATCH_STATUS => $status,
            ]);
        }
    }

    private function makeComponent(int $perPage = 3): ImportManager
    {
        $component = new ImportManager;
        $component->currentRunId = $this->runId;
        $component->currentImportId = $this->importId;
        $component->step = 'staging';

        return $component;
    }

    private function getAllRowIds(): array
    {
        return ImportStagingRow::query()
            ->where(ImportStagingRow::IMPORT_RUN_ID, $this->runId)
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->sort()
            ->values()
            ->all();
    }

    private function getRowIdsByStatus(string $status): array
    {
        return ImportStagingRow::query()
            ->where(ImportStagingRow::IMPORT_RUN_ID, $this->runId)
            ->where(ImportStagingRow::MATCH_STATUS, $status)
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->sort()
            ->values()
            ->all();
    }

    public function test_select_all_selects_all_rows_across_all_pages(): void
    {
        $component = $this->makeComponent();
        $allIds = $this->getAllRowIds();

        $component->selectAll();

        $selected = collect($component->selectedRows)->sort()->values()->all();
        $this->assertSame($allIds, $selected);
        $this->assertCount(7, $component->selectedRows);
    }

    public function test_select_all_on_page_merges_with_existing_selection(): void
    {
        $component = $this->makeComponent();
        $component->selectedRows = [999];

        $component->selectAllOnPage();

        $this->assertContains(999, $component->selectedRows);
        $this->assertGreaterThan(1, count($component->selectedRows));
    }

    public function test_select_all_on_page_does_not_create_duplicates(): void
    {
        $component = $this->makeComponent();

        $component->selectAllOnPage();
        $countAfterFirst = count($component->selectedRows);

        $component->selectAllOnPage();
        $countAfterSecond = count($component->selectedRows);

        $this->assertSame($countAfterFirst, $countAfterSecond);
    }

    public function test_deselect_all_on_page_removes_only_current_page_ids(): void
    {
        $component = $this->makeComponent();
        $allIds = $this->getAllRowIds();

        $extraId = 99999;
        $component->selectedRows = array_merge($allIds, [$extraId]);

        $pageIds = collect($component->getStagingRowsProperty()->items())
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $component->deselectAllOnPage();

        foreach ($pageIds as $id) {
            $this->assertNotContains($id, $component->selectedRows);
        }

        $this->assertContains($extraId, $component->selectedRows);
    }

    public function test_deselect_all_clears_everything(): void
    {
        $component = $this->makeComponent();
        $component->selectedRows = $this->getAllRowIds();

        $component->deselectAll();

        $this->assertSame([], $component->selectedRows);
    }

    public function test_select_by_status_selects_all_matching_rows_across_pages(): void
    {
        $component = $this->makeComponent();
        $newIds = $this->getRowIdsByStatus('new');

        $component->selectByStatus('new');

        $selected = collect($component->selectedRows)->sort()->values()->all();
        $this->assertSame($newIds, $selected);
        $this->assertCount(3, $component->selectedRows);
    }

    public function test_select_by_status_merges_with_existing_selection(): void
    {
        $component = $this->makeComponent();

        $component->selectByStatus('new');
        $countNew = count($component->selectedRows);

        $component->selectByStatus('changed');
        $countTotal = count($component->selectedRows);

        $this->assertSame(3, $countNew);
        $this->assertSame(5, $countTotal);
    }

    public function test_toggle_row_does_not_affect_other_selections(): void
    {
        $component = $this->makeComponent();
        $allIds = $this->getAllRowIds();
        $component->selectedRows = $allIds;

        $removedId = $allIds[0];
        $component->toggleRow($removedId);

        $this->assertNotContains($removedId, $component->selectedRows);
        $this->assertCount(count($allIds) - 1, $component->selectedRows);
    }

    public function test_selection_persists_between_page_changes(): void
    {
        $component = $this->makeComponent();

        $component->selectAllOnPage();
        $page1Selection = $component->selectedRows;

        $this->assertNotEmpty($page1Selection);

        foreach ($page1Selection as $id) {
            $this->assertContains($id, $component->selectedRows);
        }
    }

    public function test_select_all_respects_status_filters(): void
    {
        $component = $this->makeComponent();
        $component->toggleStatusFilter('new');

        $newIds = $this->getRowIdsByStatus('new');

        $component->selectAll();

        $selected = collect($component->selectedRows)->sort()->values()->all();
        $this->assertSame($newIds, $selected);
        $this->assertCount(3, $component->selectedRows);
    }
}
