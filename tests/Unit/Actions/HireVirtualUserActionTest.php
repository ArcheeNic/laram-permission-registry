<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\HireVirtualUserAction;
use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Jobs\GrantMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Services\HrEventTriggerExecutor;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use Mockery;

class HireVirtualUserActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_hire_activates_user_assigns_relations_and_reconciles_permissions(): void
    {
        $user = VirtualUser::create([
            'name' => 'Candidate',
            'status' => VirtualUserStatus::DEACTIVATED,
            'employee_category' => EmployeeCategory::STAFF,
        ]);

        $permission = Permission::create(['service' => 'test', 'name' => 'access', 'auto_grant' => true]);
        $position = Position::create(['name' => 'QA']);
        $position->permissions()->attach($permission->id);

        $permissionGroup = PermissionGroup::create(['name' => 'Team']);

        $mockExecutor = Mockery::mock(HrEventTriggerExecutor::class);
        $mockExecutor->shouldReceive('execute')->once()->with($user->id, 'hire')->andReturn(true);
        $this->app->instance(HrEventTriggerExecutor::class, $mockExecutor);

        app(HireVirtualUserAction::class)->handle(
            $user->id,
            [$position->id],
            [$permissionGroup->id],
            EmployeeCategory::CONTRACTOR
        );

        $user->refresh();

        $this->assertSame(VirtualUserStatus::ACTIVE, $user->status);
        $this->assertSame(EmployeeCategory::CONTRACTOR, $user->employee_category);
        $this->assertDatabaseHas('virtual_user_positions', [
            'virtual_user_id' => $user->id,
            'position_id' => $position->id,
        ]);
        $this->assertDatabaseHas('virtual_user_groups', [
            'virtual_user_id' => $user->id,
            'permission_group_id' => $permissionGroup->id,
        ]);

        Queue::assertPushed(GrantMultiplePermissionsJob::class);
    }

    public function test_hire_throws_for_unknown_category(): void
    {
        $user = VirtualUser::create([
            'name' => 'Candidate',
            'status' => VirtualUserStatus::DEACTIVATED,
            'employee_category' => EmployeeCategory::STAFF,
        ]);

        $mockExecutor = Mockery::mock(HrEventTriggerExecutor::class);
        $mockExecutor->shouldNotReceive('execute');
        $this->app->instance(HrEventTriggerExecutor::class, $mockExecutor);

        $this->expectException(\InvalidArgumentException::class);
        app(HireVirtualUserAction::class)->handle($user->id, [], [], 'unknown-category');
    }
}
