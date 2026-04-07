<?php

namespace ArcheeNic\PermissionRegistry\Tests\Feature\Actions;

use ArcheeNic\PermissionRegistry\Actions\CreateVirtualUserAction;
use ArcheeNic\PermissionRegistry\Actions\ExecuteApprovedImportAction;
use ArcheeNic\PermissionRegistry\Actions\FireVirtualUserAction;
use ArcheeNic\PermissionRegistry\Actions\GrantPermissionAction;
use ArcheeNic\PermissionRegistry\Actions\HireVirtualUserAction;
use ArcheeNic\PermissionRegistry\Actions\RevokePermissionAction;
use ArcheeNic\PermissionRegistry\Actions\UpdateVirtualUserGlobalFieldsAction;
use ArcheeNic\PermissionRegistry\Enums\ImportExecutionStatus;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\ImportExecutionLog;
use ArcheeNic\PermissionRegistry\Models\ImportFieldMapping;
use ArcheeNic\PermissionRegistry\Models\ImportStagingRow;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\PermissionImport;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Str;
use Mockery;

class ExecuteApprovedImportActionTest extends TestCase
{
    private string $importRunId;

    private PermissionImport $import;

    private Permission $permission;

    private PermissionField $emailField;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importRunId = (string) Str::uuid();

        $this->emailField = PermissionField::create([
            'name' => 'email',
            'is_global' => true,
        ]);

        $this->permission = Permission::create([
            'service' => 'test-import',
            'name' => 'imported-access',
            'description' => 'Test import permission',
        ]);

        $this->emailField->permissions()->attach($this->permission->id);

        $this->import = PermissionImport::create([
            'name' => 'Test Import',
            'class_name' => 'App\\Imports\\TestImporter',
            'description' => 'Test',
            'is_active' => true,
        ]);

        ImportFieldMapping::create([
            'permission_import_id' => $this->import->id,
            'import_field_name' => 'email',
            'permission_field_id' => $this->emailField->id,
            'is_internal' => false,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function test_execute_new_creates_virtual_user_hires_and_grants_permission(): void
    {
        $createUserMock = Mockery::mock(CreateVirtualUserAction::class);
        $hireUserMock = Mockery::mock(HireVirtualUserAction::class);
        $grantMock = Mockery::mock(GrantPermissionAction::class);

        $newUser = VirtualUser::create(['name' => 'New User', 'status' => VirtualUserStatus::ACTIVE]);

        $createUserMock->shouldReceive('handle')
            ->once()
            ->andReturn($newUser);

        $hireUserMock->shouldReceive('handle')
            ->once()
            ->andReturn($newUser);

        $grantMock->shouldReceive('handle')
            ->once()
            ->andReturn(Mockery::mock(\ArcheeNic\PermissionRegistry\Models\GrantedPermission::class));

        $this->app->instance(CreateVirtualUserAction::class, $createUserMock);
        $this->app->instance(HireVirtualUserAction::class, $hireUserMock);
        $this->app->instance(GrantPermissionAction::class, $grantMock);

        ImportExecutionLog::create([
            'import_run_id' => $this->importRunId,
            'permission_import_id' => $this->import->id,
            'status' => ImportExecutionStatus::RUNNING->value,
            'started_at' => now(),
        ]);

        ImportStagingRow::create([
            'import_run_id' => $this->importRunId,
            'permission_import_id' => $this->import->id,
            'external_id' => 'ext-new',
            'fields' => ['email' => 'new@test.com'],
            'match_status' => 'new',
            'matched_virtual_user_id' => null,
            'is_approved' => true,
        ]);

        $action = app(ExecuteApprovedImportAction::class);
        $action->handle($this->importRunId);

        $this->assertDatabaseHas('import_execution_logs', [
            'import_run_id' => $this->importRunId,
        ]);
    }

    public function test_execute_changed_updates_virtual_user_global_fields(): void
    {
        $user = VirtualUser::create(['name' => 'Existing', 'status' => VirtualUserStatus::ACTIVE]);

        $updateFieldsMock = Mockery::mock(UpdateVirtualUserGlobalFieldsAction::class);
        $updateFieldsMock->shouldReceive('execute')
            ->once();

        $this->app->instance(UpdateVirtualUserGlobalFieldsAction::class, $updateFieldsMock);

        ImportStagingRow::create([
            'import_run_id' => $this->importRunId,
            'permission_import_id' => $this->import->id,
            'external_id' => 'ext-changed',
            'fields' => ['email' => 'updated@test.com'],
            'match_status' => 'changed',
            'matched_virtual_user_id' => $user->id,
            'is_approved' => true,
        ]);

        $action = app(ExecuteApprovedImportAction::class);
        $action->handle($this->importRunId);
    }

    public function test_execute_missing_revokes_permission_and_fires_user(): void
    {
        $user = VirtualUser::create(['name' => 'Leaving', 'status' => VirtualUserStatus::ACTIVE]);

        $revokeMock = Mockery::mock(RevokePermissionAction::class);
        $revokeMock->shouldReceive('handle')
            ->once()
            ->andReturn(true);

        $fireMock = Mockery::mock(FireVirtualUserAction::class);
        $fireMock->shouldReceive('handle')
            ->once()
            ->andReturn($user);

        $this->app->instance(RevokePermissionAction::class, $revokeMock);
        $this->app->instance(FireVirtualUserAction::class, $fireMock);

        ImportStagingRow::create([
            'import_run_id' => $this->importRunId,
            'permission_import_id' => $this->import->id,
            'external_id' => 'ext-missing',
            'fields' => [],
            'match_status' => 'missing',
            'matched_virtual_user_id' => $user->id,
            'is_approved' => true,
        ]);

        $action = app(ExecuteApprovedImportAction::class);
        $action->handle($this->importRunId);
    }

    public function test_execute_exists_does_nothing(): void
    {
        $user = VirtualUser::create(['name' => 'Same', 'status' => VirtualUserStatus::ACTIVE]);

        $createUserMock = Mockery::mock(CreateVirtualUserAction::class);
        $createUserMock->shouldNotReceive('handle');
        $this->app->instance(CreateVirtualUserAction::class, $createUserMock);

        $updateFieldsMock = Mockery::mock(UpdateVirtualUserGlobalFieldsAction::class);
        $updateFieldsMock->shouldNotReceive('execute');
        $this->app->instance(UpdateVirtualUserGlobalFieldsAction::class, $updateFieldsMock);

        ImportStagingRow::create([
            'import_run_id' => $this->importRunId,
            'permission_import_id' => $this->import->id,
            'external_id' => 'ext-exists',
            'fields' => ['email' => 'same@test.com'],
            'match_status' => 'exists',
            'matched_virtual_user_id' => $user->id,
            'is_approved' => true,
        ]);

        $action = app(ExecuteApprovedImportAction::class);
        $action->handle($this->importRunId);
    }

    public function test_unapproved_rows_are_skipped(): void
    {
        $createUserMock = Mockery::mock(CreateVirtualUserAction::class);
        $createUserMock->shouldNotReceive('handle');
        $this->app->instance(CreateVirtualUserAction::class, $createUserMock);

        ImportStagingRow::create([
            'import_run_id' => $this->importRunId,
            'permission_import_id' => $this->import->id,
            'external_id' => 'ext-unapproved',
            'fields' => ['email' => 'skip@test.com'],
            'match_status' => 'new',
            'matched_virtual_user_id' => null,
            'is_approved' => null,
        ]);

        ImportStagingRow::create([
            'import_run_id' => $this->importRunId,
            'permission_import_id' => $this->import->id,
            'external_id' => 'ext-rejected',
            'fields' => ['email' => 'rejected@test.com'],
            'match_status' => 'new',
            'matched_virtual_user_id' => null,
            'is_approved' => false,
        ]);

        $action = app(ExecuteApprovedImportAction::class);
        $action->handle($this->importRunId);
    }

    public function test_execution_creates_log_entry(): void
    {
        $user = VirtualUser::create(['name' => 'LogTest', 'status' => VirtualUserStatus::ACTIVE]);

        ImportExecutionLog::create([
            'import_run_id' => $this->importRunId,
            'permission_import_id' => $this->import->id,
            'status' => ImportExecutionStatus::RUNNING->value,
            'started_at' => now(),
        ]);

        ImportStagingRow::create([
            'import_run_id' => $this->importRunId,
            'permission_import_id' => $this->import->id,
            'external_id' => 'ext-log',
            'fields' => ['email' => 'log@test.com'],
            'match_status' => 'exists',
            'matched_virtual_user_id' => $user->id,
            'is_approved' => true,
        ]);

        $action = app(ExecuteApprovedImportAction::class);
        $action->handle($this->importRunId);

        $this->assertDatabaseHas('import_execution_logs', [
            'import_run_id' => $this->importRunId,
            'permission_import_id' => $this->import->id,
        ]);

        $log = ImportExecutionLog::where('import_run_id', $this->importRunId)->first();
        $this->assertNotNull($log->started_at);
        $this->assertNotNull($log->completed_at);
    }
}
