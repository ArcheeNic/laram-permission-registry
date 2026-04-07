<?php

namespace ArcheeNic\PermissionRegistry\Tests\Feature\Actions;

use ArcheeNic\PermissionRegistry\Actions\ApproveImportRowsAction;
use ArcheeNic\PermissionRegistry\Models\ImportStagingRow;
use ArcheeNic\PermissionRegistry\Models\PermissionImport;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Str;

class ApproveImportRowsActionTest extends TestCase
{
    private string $importRunId;

    private PermissionImport $import;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importRunId = (string) Str::uuid();
        $this->import = PermissionImport::create([
            'name' => 'Test Import',
            'class_name' => 'App\\Imports\\TestImporter',
            'description' => 'Test',
            'is_active' => true,
        ]);
    }

    public function test_approve_marks_selected_rows_as_approved(): void
    {
        $row1 = ImportStagingRow::create([
            'import_run_id' => $this->importRunId,
            'permission_import_id' => $this->import->id,
            'external_id' => 'ext-1',
            'fields' => ['email' => 'a@test.com'],
            'match_status' => 'new',
            'is_approved' => null,
        ]);

        $row2 = ImportStagingRow::create([
            'import_run_id' => $this->importRunId,
            'permission_import_id' => $this->import->id,
            'external_id' => 'ext-2',
            'fields' => ['email' => 'b@test.com'],
            'match_status' => 'new',
            'is_approved' => null,
        ]);

        $action = app(ApproveImportRowsAction::class);
        $action->handle($this->importRunId, [$row1->id, $row2->id]);

        $this->assertTrue(ImportStagingRow::find($row1->id)->is_approved);
        $this->assertTrue(ImportStagingRow::find($row2->id)->is_approved);
    }

    public function test_approve_only_selected_rows_leaves_others_unapproved(): void
    {
        $row1 = ImportStagingRow::create([
            'import_run_id' => $this->importRunId,
            'permission_import_id' => $this->import->id,
            'external_id' => 'ext-1',
            'fields' => ['email' => 'a@test.com'],
            'match_status' => 'new',
            'is_approved' => null,
        ]);

        $row2 = ImportStagingRow::create([
            'import_run_id' => $this->importRunId,
            'permission_import_id' => $this->import->id,
            'external_id' => 'ext-2',
            'fields' => ['email' => 'b@test.com'],
            'match_status' => 'changed',
            'is_approved' => null,
        ]);

        $action = app(ApproveImportRowsAction::class);
        $action->handle($this->importRunId, [$row1->id]);

        $this->assertTrue(ImportStagingRow::find($row1->id)->is_approved);
        $this->assertFalse(ImportStagingRow::find($row2->id)->is_approved);
    }

    public function test_approve_does_not_affect_rows_from_other_runs(): void
    {
        $otherRunId = (string) Str::uuid();

        $row = ImportStagingRow::create([
            'import_run_id' => $this->importRunId,
            'permission_import_id' => $this->import->id,
            'external_id' => 'ext-1',
            'fields' => [],
            'match_status' => 'new',
            'is_approved' => null,
        ]);

        $otherRow = ImportStagingRow::create([
            'import_run_id' => $otherRunId,
            'permission_import_id' => $this->import->id,
            'external_id' => 'ext-2',
            'fields' => [],
            'match_status' => 'new',
            'is_approved' => null,
        ]);

        $action = app(ApproveImportRowsAction::class);
        $action->handle($this->importRunId, [$row->id, $otherRow->id]);

        $this->assertTrue(ImportStagingRow::find($row->id)->is_approved);
        $this->assertNull(ImportStagingRow::find($otherRow->id)->is_approved);
    }
}
