<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Livewire;

use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Livewire\ImportManager;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\ImportFieldMapping;
use ArcheeNic\PermissionRegistry\Models\ImportStagingRow;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\PermissionImport;
use ArcheeNic\PermissionRegistry\Models\PermissionTriggerAssignment;
use ArcheeNic\PermissionRegistry\Models\PermissionTrigger;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Str;

class ImportManagerExistsActionsTest extends TestCase
{
    private string $runId;

    private int $importId;

    private Permission $permissionA;

    private Permission $permissionB;

    private VirtualUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->runId = (string) Str::uuid();

        $emailField = PermissionField::create([
            'name' => 'email',
            'is_global' => true,
        ]);

        $this->permissionA = Permission::create([
            'service' => 'test',
            'name' => 'access-a',
        ]);

        $this->permissionB = Permission::create([
            'service' => 'test',
            'name' => 'access-b',
        ]);

        $trigger = PermissionTrigger::create([
            'class_name' => 'App\\Triggers\\Bitrix24AddToDepartmentTrigger',
            'name' => 'B24 Dept',
        ]);

        PermissionTriggerAssignment::create([
            'permission_id' => $this->permissionA->id,
            'permission_trigger_id' => $trigger->id,
            'event_type' => 'grant',
            'is_enabled' => true,
            'config' => ['department_id' => '412'],
        ]);

        PermissionTriggerAssignment::create([
            'permission_id' => $this->permissionB->id,
            'permission_trigger_id' => $trigger->id,
            'event_type' => 'grant',
            'is_enabled' => true,
            'config' => ['department_id' => '412'],
        ]);

        $import = PermissionImport::create([
            'name' => 'B24 Import',
            'class_name' => 'App\\Imports\\Bitrix24Import',
            'is_active' => true,
        ]);

        $this->importId = $import->id;

        ImportFieldMapping::create([
            'permission_import_id' => $import->id,
            'import_field_name' => 'email',
            'permission_field_id' => $emailField->id,
            'is_internal' => true,
        ]);

        $this->user = VirtualUser::create([
            'name' => 'Existing User',
            'status' => VirtualUserStatus::ACTIVE,
        ]);

        GrantedPermission::create([
            'virtual_user_id' => $this->user->id,
            'permission_id' => $this->permissionA->id,
            'enabled' => true,
        ]);
    }

    private function makeComponent(): ImportManager
    {
        $component = new ImportManager;
        $component->currentRunId = $this->runId;
        $component->currentImportId = $this->importId;
        $component->step = 'staging';

        return $component;
    }

    public function test_exists_row_shows_permission_diff_in_actions(): void
    {
        ImportStagingRow::create([
            'import_run_id' => $this->runId,
            'permission_import_id' => $this->importId,
            'external_id' => 'ext-exists',
            'fields' => ['email' => 'user@test.com', 'department_ids' => '412'],
            'match_status' => 'exists',
            'matched_virtual_user_id' => $this->user->id,
            'is_approved' => true,
        ]);

        $component = $this->makeComponent();
        $actions = $component->getRowActionsProperty();
        $rowId = ImportStagingRow::where('external_id', 'ext-exists')->value('id');

        $items = $actions[$rowId]['items'] ?? [];
        $texts = array_map(fn (array $item) => $item['text'], $items);
        $allText = implode(' ', $texts);

        $this->assertStringContainsString($this->permissionB->name, $allText);
    }

    public function test_exists_row_without_changes_shows_no_changes(): void
    {
        GrantedPermission::create([
            'virtual_user_id' => $this->user->id,
            'permission_id' => $this->permissionB->id,
            'enabled' => true,
        ]);

        ImportStagingRow::create([
            'import_run_id' => $this->runId,
            'permission_import_id' => $this->importId,
            'external_id' => 'ext-exists-same',
            'fields' => ['email' => 'user@test.com', 'department_ids' => '412'],
            'match_status' => 'exists',
            'matched_virtual_user_id' => $this->user->id,
            'is_approved' => true,
        ]);

        $component = $this->makeComponent();
        $actions = $component->getRowActionsProperty();
        $rowId = ImportStagingRow::where('external_id', 'ext-exists-same')->value('id');

        $items = $actions[$rowId]['items'] ?? [];
        $icons = array_column($items, 'icon');

        $this->assertContains('check', $icons);
    }
}
