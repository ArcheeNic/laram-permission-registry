<?php

namespace ArcheeNic\PermissionRegistry\Tests\Feature\Actions;

use ArcheeNic\PermissionRegistry\Actions\CreateVirtualUserAction;
use ArcheeNic\PermissionRegistry\Actions\CleanupImportRunAction;
use ArcheeNic\PermissionRegistry\Actions\ExecuteApprovedImportAction;
use ArcheeNic\PermissionRegistry\Actions\FireVirtualUserAction;
use ArcheeNic\PermissionRegistry\Actions\GrantPermissionAction;
use ArcheeNic\PermissionRegistry\Actions\HireVirtualUserAction;
use ArcheeNic\PermissionRegistry\Actions\RevokePermissionAction;
use ArcheeNic\PermissionRegistry\Actions\UpdateVirtualUserGlobalFieldsAction;
use ArcheeNic\PermissionRegistry\Enums\ImportExecutionStatus;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\ImportExecutionLog;
use ArcheeNic\PermissionRegistry\Models\ImportFieldMapping;
use ArcheeNic\PermissionRegistry\Models\ImportStagingRow;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\PermissionImport;
use ArcheeNic\PermissionRegistry\Services\ImportFieldMappingService;
use ArcheeNic\PermissionRegistry\Services\ImportTriggerConfigResolver;
use ArcheeNic\PermissionRegistry\Services\TriggerPermissionMatcherService;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Str;
use Mockery;

class ExecuteApprovedImportActionTest extends TestCase
{
    private string $importRunId;

    private PermissionImport $import;

    private Permission $permissionA;

    private Permission $permissionB;

    private PermissionField $emailField;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importRunId = (string) Str::uuid();

        $this->emailField = PermissionField::create([
            'name' => 'email',
            'is_global' => true,
        ]);

        $this->permissionA = Permission::create([
            'service' => 'test-import',
            'name' => 'imported-access-a',
            'description' => 'Test import permission A',
        ]);

        $this->permissionB = Permission::create([
            'service' => 'test-import',
            'name' => 'imported-access-b',
            'description' => 'Test import permission B',
        ]);

        $this->emailField->permissions()->attach($this->permissionA->id);
        $this->emailField->permissions()->attach($this->permissionB->id);

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

    public function test_execute_new_creates_virtual_user_hires_and_grants_all_matched_permissions(): void
    {
        $createUserMock = Mockery::mock(CreateVirtualUserAction::class);
        $hireUserMock = Mockery::mock(HireVirtualUserAction::class);
        $grantMock = Mockery::mock(GrantPermissionAction::class);
        $matcherMock = Mockery::mock(TriggerPermissionMatcherService::class);

        $newUser = VirtualUser::create(['name' => 'New User', 'status' => VirtualUserStatus::ACTIVE]);

        $createUserMock->shouldReceive('handle')
            ->once()
            ->andReturn($newUser);

        $hireUserMock->shouldReceive('handle')
            ->once()
            ->andReturn($newUser);

        $grantMock->shouldReceive('handle')
            ->twice()
            ->andReturn(Mockery::mock(\ArcheeNic\PermissionRegistry\Models\GrantedPermission::class));

        $matcherMock->shouldReceive('matchByDepartments')
            ->once()
            ->andReturn(collect([
                ['permission_id' => $this->permissionA->id, 'department_id' => '1', 'permission_name' => $this->permissionA->name],
                ['permission_id' => $this->permissionB->id, 'department_id' => '15', 'permission_name' => $this->permissionB->name],
            ]));
        $matcherMock->shouldReceive('normalizeDepartmentIds')
            ->once()
            ->andReturn(['1', '15']);

        $matcherMock->shouldReceive('getAllManagedPermissionIds')
            ->once()
            ->andReturn([$this->permissionA->id, $this->permissionB->id]);

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
            'fields' => ['email' => 'new@test.com', 'department_ids' => '1,15'],
            'match_status' => 'new',
            'matched_virtual_user_id' => null,
            'is_approved' => true,
        ]);

        $action = $this->makeAction(
            createVirtualUserAction: $createUserMock,
            hireVirtualUserAction: $hireUserMock,
            grantPermissionAction: $grantMock,
            triggerPermissionMatcherService: $matcherMock
        );
        $action->handle($this->importRunId);

        $this->assertDatabaseHas('import_execution_logs', [
            'import_run_id' => $this->importRunId,
        ]);
    }

    public function test_execute_changed_reconciles_managed_permissions(): void
    {
        $user = VirtualUser::create(['name' => 'Existing', 'status' => VirtualUserStatus::ACTIVE]);
        GrantedPermission::create(['virtual_user_id' => $user->id, 'permission_id' => $this->permissionA->id, 'enabled' => true]);

        $updateFieldsMock = Mockery::mock(UpdateVirtualUserGlobalFieldsAction::class);
        $updateFieldsMock->shouldReceive('execute')
            ->once();
        $grantMock = Mockery::mock(GrantPermissionAction::class);
        $revokeMock = Mockery::mock(RevokePermissionAction::class);
        $matcherMock = Mockery::mock(TriggerPermissionMatcherService::class);

        $grantMock->shouldReceive('handle')->once()->andReturn(Mockery::mock(GrantedPermission::class));
        $revokeMock->shouldReceive('handle')->once()->andReturn(true);

        $matcherMock->shouldReceive('matchByDepartments')
            ->once()
            ->andReturn(collect([
                ['permission_id' => $this->permissionB->id, 'department_id' => '15', 'permission_name' => $this->permissionB->name],
            ]));
        $matcherMock->shouldReceive('normalizeDepartmentIds')
            ->once()
            ->andReturn(['15']);
        $matcherMock->shouldReceive('getAllManagedPermissionIds')
            ->once()
            ->andReturn([$this->permissionA->id, $this->permissionB->id]);

        ImportStagingRow::create([
            'import_run_id' => $this->importRunId,
            'permission_import_id' => $this->import->id,
            'external_id' => 'ext-changed',
            'fields' => ['email' => 'updated@test.com', 'department_ids' => '15'],
            'match_status' => 'changed',
            'matched_virtual_user_id' => $user->id,
            'is_approved' => true,
        ]);

        $action = $this->makeAction(
            updateVirtualUserGlobalFieldsAction: $updateFieldsMock,
            grantPermissionAction: $grantMock,
            revokePermissionAction: $revokeMock,
            triggerPermissionMatcherService: $matcherMock
        );
        $action->handle($this->importRunId);
    }

    public function test_execute_missing_revokes_all_managed_permissions_and_fires_user(): void
    {
        $user = VirtualUser::create(['name' => 'Leaving', 'status' => VirtualUserStatus::ACTIVE]);
        GrantedPermission::create(['virtual_user_id' => $user->id, 'permission_id' => $this->permissionA->id, 'enabled' => true]);
        $matcherMock = Mockery::mock(TriggerPermissionMatcherService::class);

        $revokeMock = Mockery::mock(RevokePermissionAction::class);
        $revokeMock->shouldReceive('handle')
            ->once()
            ->andReturn(true);

        $fireMock = Mockery::mock(FireVirtualUserAction::class);
        $fireMock->shouldReceive('handle')
            ->once()
            ->andReturn($user);

        $matcherMock->shouldReceive('getAllManagedPermissionIds')
            ->once()
            ->andReturn([$this->permissionA->id, $this->permissionB->id]);
        $matcherMock->shouldReceive('matchByDepartments')->never();

        ImportStagingRow::create([
            'import_run_id' => $this->importRunId,
            'permission_import_id' => $this->import->id,
            'external_id' => 'ext-missing',
            'fields' => [],
            'match_status' => 'missing',
            'matched_virtual_user_id' => $user->id,
            'is_approved' => true,
        ]);

        $action = $this->makeAction(
            fireVirtualUserAction: $fireMock,
            revokePermissionAction: $revokeMock,
            triggerPermissionMatcherService: $matcherMock
        );
        $action->handle($this->importRunId);
    }

    public function test_execute_exists_syncs_permissions_without_updating_fields(): void
    {
        $user = VirtualUser::create(['name' => 'Same', 'status' => VirtualUserStatus::ACTIVE]);
        GrantedPermission::create(['virtual_user_id' => $user->id, 'permission_id' => $this->permissionA->id, 'enabled' => true]);

        $matcherMock = Mockery::mock(TriggerPermissionMatcherService::class);

        $createUserMock = Mockery::mock(CreateVirtualUserAction::class);
        $createUserMock->shouldNotReceive('handle');
        $updateFieldsMock = Mockery::mock(UpdateVirtualUserGlobalFieldsAction::class);
        $updateFieldsMock->shouldNotReceive('execute');

        $grantMock = Mockery::mock(GrantPermissionAction::class);
        $grantMock->shouldReceive('handle')->once()->andReturn(Mockery::mock(GrantedPermission::class));
        $revokeMock = Mockery::mock(RevokePermissionAction::class);
        $revokeMock->shouldReceive('handle')->once()->andReturn(true);

        $matcherMock->shouldReceive('getAllManagedPermissionIds')
            ->once()
            ->andReturn([$this->permissionA->id, $this->permissionB->id]);
        $matcherMock->shouldReceive('normalizeDepartmentIds')
            ->once()
            ->andReturn(['1']);
        $matcherMock->shouldReceive('matchByDepartments')
            ->once()
            ->andReturn(collect([
                ['permission_id' => $this->permissionB->id, 'department_id' => '1', 'permission_name' => $this->permissionB->name],
            ]));

        ImportStagingRow::create([
            'import_run_id' => $this->importRunId,
            'permission_import_id' => $this->import->id,
            'external_id' => 'ext-exists',
            'fields' => ['email' => 'same@test.com', 'department_ids' => '1'],
            'match_status' => 'exists',
            'matched_virtual_user_id' => $user->id,
            'is_approved' => true,
        ]);

        $action = $this->makeAction(
            createVirtualUserAction: $createUserMock,
            updateVirtualUserGlobalFieldsAction: $updateFieldsMock,
            grantPermissionAction: $grantMock,
            revokePermissionAction: $revokeMock,
            triggerPermissionMatcherService: $matcherMock
        );
        $stats = $action->handle($this->importRunId);

        $this->assertSame(1, $stats['synced']);
        $this->assertSame(0, $stats['skipped']);
    }

    public function test_execute_exists_without_virtual_user_is_skipped(): void
    {
        $matcherMock = Mockery::mock(TriggerPermissionMatcherService::class);

        $matcherMock->shouldReceive('getAllManagedPermissionIds')
            ->once()
            ->andReturn([$this->permissionA->id]);
        $matcherMock->shouldReceive('matchByDepartments')->never();

        ImportStagingRow::create([
            'import_run_id' => $this->importRunId,
            'permission_import_id' => $this->import->id,
            'external_id' => 'ext-exists-no-user',
            'fields' => ['email' => 'nouser@test.com'],
            'match_status' => 'exists',
            'matched_virtual_user_id' => null,
            'is_approved' => true,
        ]);

        $action = $this->makeAction(triggerPermissionMatcherService: $matcherMock);
        $stats = $action->handle($this->importRunId);

        $this->assertSame(0, $stats['synced']);
        $this->assertSame(1, $stats['skipped']);
    }

    public function test_unapproved_rows_are_skipped(): void
    {
        $createUserMock = Mockery::mock(CreateVirtualUserAction::class);
        $createUserMock->shouldNotReceive('handle');
        $matcherMock = Mockery::mock(TriggerPermissionMatcherService::class);
        $matcherMock->shouldNotReceive('getAllManagedPermissionIds');

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

        $action = $this->makeAction(
            createVirtualUserAction: $createUserMock,
            triggerPermissionMatcherService: $matcherMock
        );
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

        $action = $this->makeAction();
        $action->handle($this->importRunId);

        $this->assertDatabaseHas('import_execution_logs', [
            'import_run_id' => $this->importRunId,
            'permission_import_id' => $this->import->id,
        ]);

        $log = ImportExecutionLog::where('import_run_id', $this->importRunId)->first();
        $this->assertNotNull($log->started_at);
        $this->assertNotNull($log->completed_at);
    }

    private function makeAction(
        ?CreateVirtualUserAction $createVirtualUserAction = null,
        ?HireVirtualUserAction $hireVirtualUserAction = null,
        ?FireVirtualUserAction $fireVirtualUserAction = null,
        ?GrantPermissionAction $grantPermissionAction = null,
        ?RevokePermissionAction $revokePermissionAction = null,
        ?UpdateVirtualUserGlobalFieldsAction $updateVirtualUserGlobalFieldsAction = null,
        ?TriggerPermissionMatcherService $triggerPermissionMatcherService = null
    ): ExecuteApprovedImportAction {
        return new ExecuteApprovedImportAction(
            $createVirtualUserAction ?? app(CreateVirtualUserAction::class),
            $hireVirtualUserAction ?? app(HireVirtualUserAction::class),
            $fireVirtualUserAction ?? app(FireVirtualUserAction::class),
            $grantPermissionAction ?? app(GrantPermissionAction::class),
            $revokePermissionAction ?? app(RevokePermissionAction::class),
            $updateVirtualUserGlobalFieldsAction ?? app(UpdateVirtualUserGlobalFieldsAction::class),
            app(ImportFieldMappingService::class),
            app(ImportTriggerConfigResolver::class),
            $triggerPermissionMatcherService ?? app(TriggerPermissionMatcherService::class),
            app(CleanupImportRunAction::class),
        );
    }
}
