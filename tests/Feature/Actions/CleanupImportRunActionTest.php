<?php

namespace ArcheeNic\PermissionRegistry\Tests\Feature\Actions;

use ArcheeNic\PermissionRegistry\Actions\CleanupImportRunAction;
use ArcheeNic\PermissionRegistry\Models\ImportStagingRow;
use ArcheeNic\PermissionRegistry\Models\PermissionImport;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Str;

class CleanupImportRunActionTest extends TestCase
{
    private PermissionImport $import;

    protected function setUp(): void
    {
        parent::setUp();
        $this->import = PermissionImport::create([
            'name' => 'Test Import',
            'class_name' => 'App\\Imports\\TestImporter',
            'description' => 'Test',
            'is_active' => true,
        ]);
    }

    public function test_cleanup_deletes_all_staging_rows_for_given_run(): void
    {
        $importRunId = (string) Str::uuid();

        ImportStagingRow::create([
            'import_run_id' => $importRunId,
            'permission_import_id' => $this->import->id,
            'external_id' => 'ext-1',
            'fields' => ['email' => 'a@test.com'],
            'match_status' => 'new',
            'is_approved' => true,
        ]);

        ImportStagingRow::create([
            'import_run_id' => $importRunId,
            'permission_import_id' => $this->import->id,
            'external_id' => 'ext-2',
            'fields' => ['email' => 'b@test.com'],
            'match_status' => 'changed',
            'is_approved' => false,
        ]);

        $action = app(CleanupImportRunAction::class);
        $action->handle($importRunId);

        $this->assertSame(0, ImportStagingRow::where('import_run_id', $importRunId)->count());
    }

    public function test_cleanup_does_not_delete_rows_from_other_runs(): void
    {
        $targetRunId = (string) Str::uuid();
        $otherRunId = (string) Str::uuid();

        ImportStagingRow::create([
            'import_run_id' => $targetRunId,
            'permission_import_id' => $this->import->id,
            'external_id' => 'ext-1',
            'fields' => [],
            'match_status' => 'new',
        ]);

        ImportStagingRow::create([
            'import_run_id' => $otherRunId,
            'permission_import_id' => $this->import->id,
            'external_id' => 'ext-2',
            'fields' => [],
            'match_status' => 'new',
        ]);

        $action = app(CleanupImportRunAction::class);
        $action->handle($targetRunId);

        $this->assertSame(0, ImportStagingRow::where('import_run_id', $targetRunId)->count());
        $this->assertSame(1, ImportStagingRow::where('import_run_id', $otherRunId)->count());
    }

    public function test_cleanup_handles_empty_run_gracefully(): void
    {
        $emptyRunId = (string) Str::uuid();

        $action = app(CleanupImportRunAction::class);
        $action->handle($emptyRunId);

        $this->assertSame(0, ImportStagingRow::where('import_run_id', $emptyRunId)->count());
    }
}
