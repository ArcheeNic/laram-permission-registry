<?php

namespace ArcheeNic\PermissionRegistry\Tests\Feature\Actions;

use ArcheeNic\PermissionRegistry\Actions\FetchImportAction;
use ArcheeNic\PermissionRegistry\Contracts\PermissionImportInterface;
use ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\ImportFieldMapping;
use ArcheeNic\PermissionRegistry\Models\ImportStagingRow;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\PermissionImport;
use ArcheeNic\PermissionRegistry\Models\PermissionTrigger;
use ArcheeNic\PermissionRegistry\Models\PermissionTriggerAssignment;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use ArcheeNic\PermissionRegistry\ValueObjects\ImportContext;
use ArcheeNic\PermissionRegistry\ValueObjects\ImportResult;

class FetchImportStatusRecalculationTest extends TestCase
{
    private PermissionField $emailField;

    private PermissionImport $import;

    private Permission $permissionA;

    private Permission $permissionB;

    private PermissionTrigger $trigger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->emailField = PermissionField::create([
            'name' => 'email',
            'is_global' => true,
        ]);

        $this->permissionA = Permission::create([
            'service' => 'test',
            'name' => 'access-alpha',
        ]);

        $this->permissionB = Permission::create([
            'service' => 'test',
            'name' => 'access-beta',
        ]);

        $this->trigger = PermissionTrigger::create([
            'class_name' => 'App\\Triggers\\Bitrix24TestTrigger',
            'name' => 'Test Trigger',
        ]);

        PermissionTriggerAssignment::create([
            'permission_id' => $this->permissionA->id,
            'permission_trigger_id' => $this->trigger->id,
            'event_type' => 'grant',
            'is_enabled' => true,
            'config' => ['department_id' => '100'],
        ]);

        PermissionTriggerAssignment::create([
            'permission_id' => $this->permissionB->id,
            'permission_trigger_id' => $this->trigger->id,
            'event_type' => 'grant',
            'is_enabled' => true,
            'config' => ['department_id' => '200'],
        ]);

        $this->import = PermissionImport::create([
            'name' => 'Status Recalc Test',
            'class_name' => StatusRecalcTestImporter::class,
            'is_active' => true,
        ]);

        ImportFieldMapping::create([
            'permission_import_id' => $this->import->id,
            'import_field_name' => 'email',
            'permission_field_id' => $this->emailField->id,
            'is_internal' => true,
        ]);
    }

    public function test_exists_row_upgraded_to_changed_when_permission_needs_adding(): void
    {
        $user = VirtualUser::create(['name' => 'Alice', 'status' => VirtualUserStatus::ACTIVE]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $this->emailField->id,
            'value' => 'alice@test.com',
        ]);

        $this->registerImporter([
            ['external_id' => 'ext-1', 'email' => 'alice@test.com', 'department_ids' => '100'],
        ]);

        $runId = app(FetchImportAction::class)->handle($this->import->id);

        $row = ImportStagingRow::where('import_run_id', $runId)
            ->where('external_id', 'ext-1')
            ->first();

        $this->assertSame(
            ImportMatchStatus::CHANGED,
            $row->match_status,
            'Row should be CHANGED when user has no permission but import triggers one'
        );
    }

    public function test_exists_row_upgraded_to_changed_when_permission_needs_revoking(): void
    {
        $user = VirtualUser::create(['name' => 'Bob', 'status' => VirtualUserStatus::ACTIVE]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $this->emailField->id,
            'value' => 'bob@test.com',
        ]);
        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $this->permissionA->id,
            'enabled' => true,
        ]);

        $this->registerImporter([
            ['external_id' => 'ext-2', 'email' => 'bob@test.com', 'department_ids' => '200'],
        ]);

        $runId = app(FetchImportAction::class)->handle($this->import->id);

        $row = ImportStagingRow::where('import_run_id', $runId)
            ->where('external_id', 'ext-2')
            ->first();

        $this->assertSame(
            ImportMatchStatus::CHANGED,
            $row->match_status,
            'Row should be CHANGED when existing permission needs revoking'
        );
    }

    public function test_exists_row_stays_exists_when_permissions_fully_match(): void
    {
        $user = VirtualUser::create(['name' => 'Carol', 'status' => VirtualUserStatus::ACTIVE]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $this->emailField->id,
            'value' => 'carol@test.com',
        ]);
        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $this->permissionA->id,
            'enabled' => true,
        ]);

        $this->registerImporter([
            ['external_id' => 'ext-3', 'email' => 'carol@test.com', 'department_ids' => '100'],
        ]);

        $runId = app(FetchImportAction::class)->handle($this->import->id);

        $row = ImportStagingRow::where('import_run_id', $runId)
            ->where('external_id', 'ext-3')
            ->first();

        $this->assertSame(
            ImportMatchStatus::EXISTS,
            $row->match_status,
            'Row should remain EXISTS when permissions fully match'
        );
    }

    public function test_exists_row_stays_exists_when_no_triggers_configured(): void
    {
        PermissionTriggerAssignment::query()->delete();

        $user = VirtualUser::create(['name' => 'Dave', 'status' => VirtualUserStatus::ACTIVE]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $this->emailField->id,
            'value' => 'dave@test.com',
        ]);

        $this->registerImporter([
            ['external_id' => 'ext-4', 'email' => 'dave@test.com', 'department_ids' => '100'],
        ]);

        $runId = app(FetchImportAction::class)->handle($this->import->id);

        $row = ImportStagingRow::where('import_run_id', $runId)
            ->where('external_id', 'ext-4')
            ->first();

        $this->assertSame(ImportMatchStatus::EXISTS, $row->match_status);
    }

    public function test_stats_reflect_recalculated_statuses(): void
    {
        $userA = VirtualUser::create(['name' => 'Eve', 'status' => VirtualUserStatus::ACTIVE]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $userA->id,
            'permission_field_id' => $this->emailField->id,
            'value' => 'eve@test.com',
        ]);
        GrantedPermission::create([
            'virtual_user_id' => $userA->id,
            'permission_id' => $this->permissionA->id,
            'enabled' => true,
        ]);

        $userB = VirtualUser::create(['name' => 'Frank', 'status' => VirtualUserStatus::ACTIVE]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $userB->id,
            'permission_field_id' => $this->emailField->id,
            'value' => 'frank@test.com',
        ]);

        $this->registerImporter([
            ['external_id' => 'ext-eve', 'email' => 'eve@test.com', 'department_ids' => '100'],
            ['external_id' => 'ext-frank', 'email' => 'frank@test.com', 'department_ids' => '100'],
        ]);

        $runId = app(FetchImportAction::class)->handle($this->import->id);

        $rows = ImportStagingRow::where('import_run_id', $runId)->get();
        $existsCount = $rows->where('match_status', ImportMatchStatus::EXISTS)->count();
        $changedCount = $rows->where('match_status', ImportMatchStatus::CHANGED)->count();

        $this->assertSame(1, $existsCount, 'Eve should remain EXISTS (has permissionA, department 100 triggers permissionA)');
        $this->assertSame(1, $changedCount, 'Frank should be CHANGED (no permissions, but department 100 triggers permissionA)');
    }

    public function test_repeat_import_after_execution_shows_no_permission_diff(): void
    {
        $user = VirtualUser::create(['name' => 'Grace', 'status' => VirtualUserStatus::ACTIVE]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $this->emailField->id,
            'value' => 'grace@test.com',
        ]);
        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $this->permissionA->id,
            'enabled' => true,
        ]);
        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $this->permissionB->id,
            'enabled' => true,
        ]);

        $this->registerImporter([
            ['external_id' => 'ext-grace', 'email' => 'grace@test.com', 'department_ids' => '100,200'],
        ]);

        $runId = app(FetchImportAction::class)->handle($this->import->id);

        $row = ImportStagingRow::where('import_run_id', $runId)
            ->where('external_id', 'ext-grace')
            ->first();

        $this->assertSame(
            ImportMatchStatus::EXISTS,
            $row->match_status,
            'Repeat import with all permissions already granted should stay EXISTS'
        );
    }

    private function registerImporter(array $users): void
    {
        StatusRecalcTestImporter::$usersToReturn = $users;
        $this->app->bind(StatusRecalcTestImporter::class, fn () => new StatusRecalcTestImporter());
    }
}

class StatusRecalcTestImporter implements PermissionImportInterface
{
    public static array $usersToReturn = [];

    public function execute(ImportContext $context): ImportResult
    {
        return ImportResult::success(static::$usersToReturn);
    }

    public function getName(): string
    {
        return 'Status Recalc Test';
    }

    public function getDescription(): string
    {
        return 'Test';
    }

    public function getRequiredFields(): array
    {
        return [];
    }

    public function getConfigFields(): array
    {
        return [];
    }

    public function getRelatedTriggerClassPatterns(): array
    {
        return ['App\\Triggers\\Bitrix24%'];
    }

    public function getDepartmentFieldName(): string
    {
        return 'department_ids';
    }
}
