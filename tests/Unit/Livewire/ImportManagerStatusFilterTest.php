<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Livewire;

use ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus;
use ArcheeNic\PermissionRegistry\Livewire\ImportManager;
use ArcheeNic\PermissionRegistry\Models\ImportStagingRow;
use ArcheeNic\PermissionRegistry\Models\PermissionImport;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Str;

class ImportManagerStatusFilterTest extends TestCase
{
    private string $runId;
    private ImportManager $component;

    protected function setUp(): void
    {
        parent::setUp();

        $this->runId = (string) Str::uuid();

        $import = PermissionImport::query()->create([
            PermissionImport::NAME => 'Test Import',
            PermissionImport::CLASS_NAME => 'App\\Imports\\TestImport',
            PermissionImport::IS_ACTIVE => true,
        ]);

        foreach (['new', 'new', 'changed', 'exists', 'missing'] as $status) {
            ImportStagingRow::query()->create([
                ImportStagingRow::IMPORT_RUN_ID => $this->runId,
                ImportStagingRow::PERMISSION_IMPORT_ID => $import->id,
                ImportStagingRow::EXTERNAL_ID => Str::uuid(),
                ImportStagingRow::FIELDS => ['email' => fake()->email()],
                ImportStagingRow::MATCH_STATUS => $status,
            ]);
        }
    }

    private function makeComponent(): ImportManager
    {
        $component = new ImportManager();
        $component->currentRunId = $this->runId;
        $component->step = 'staging';

        return $component;
    }

    public function test_status_filters_defaults_to_empty_array(): void
    {
        $component = $this->makeComponent();

        $this->assertSame([], $component->statusFilters);
    }

    public function test_toggle_adds_status_to_filters(): void
    {
        $component = $this->makeComponent();

        $component->toggleStatusFilter('new');

        $this->assertSame(['new'], $component->statusFilters);
    }

    public function test_toggle_removes_status_from_filters(): void
    {
        $component = $this->makeComponent();

        $component->toggleStatusFilter('new');
        $component->toggleStatusFilter('new');

        $this->assertSame([], $component->statusFilters);
    }

    public function test_toggle_supports_multiple_statuses(): void
    {
        $component = $this->makeComponent();

        $component->toggleStatusFilter('new');
        $component->toggleStatusFilter('changed');

        $this->assertEqualsCanonicalizing(['new', 'changed'], $component->statusFilters);
    }

    public function test_toggle_ignores_invalid_status(): void
    {
        $component = $this->makeComponent();

        $component->toggleStatusFilter('invalid_status');

        $this->assertSame([], $component->statusFilters);
    }

    public function test_clear_status_filter_resets_to_empty(): void
    {
        $component = $this->makeComponent();
        $component->toggleStatusFilter('new');
        $component->toggleStatusFilter('changed');

        $component->clearStatusFilter();

        $this->assertSame([], $component->statusFilters);
    }

    public function test_filtered_rows_returns_all_when_no_filter(): void
    {
        $component = $this->makeComponent();

        $rows = $component->getStagingRowsProperty();

        $this->assertSame(5, $rows->total());
    }

    public function test_filtered_rows_with_single_status(): void
    {
        $component = $this->makeComponent();
        $component->toggleStatusFilter('new');

        $rows = $component->getStagingRowsProperty();

        $this->assertSame(2, $rows->total());
        foreach ($rows as $row) {
            $this->assertSame(ImportMatchStatus::NEW, $row->match_status);
        }
    }

    public function test_filtered_rows_with_multiple_statuses(): void
    {
        $component = $this->makeComponent();
        $component->toggleStatusFilter('new');
        $component->toggleStatusFilter('missing');

        $rows = $component->getStagingRowsProperty();

        $this->assertSame(3, $rows->total());
        foreach ($rows as $row) {
            $this->assertContains($row->match_status, [ImportMatchStatus::NEW, ImportMatchStatus::MISSING]);
        }
    }
}
