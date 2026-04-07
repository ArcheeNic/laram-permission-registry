<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\FireVirtualUserAction;
use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Jobs\RevokeMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Services\HrEventTriggerExecutor;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use Mockery;

class FireVirtualUserActionTest extends TestCase
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

    public function test_fire_deactivates_user_detaches_relations_and_revokes_auto_granted_permissions(): void
    {
        $user = VirtualUser::create([
            'name' => 'Employee',
            'status' => VirtualUserStatus::ACTIVE,
            'employee_category' => EmployeeCategory::STAFF,
        ]);

        $position = Position::create(['name' => 'Support']);
        $group = PermissionGroup::create(['name' => 'Support Group']);
        $user->positions()->attach($position->id);
        $user->groups()->attach($group->id);

        $autoPermission = Permission::create(['service' => 'test', 'name' => 'auto', 'auto_grant' => true]);
        $manualPermission = Permission::create(['service' => 'test', 'name' => 'manual', 'auto_grant' => true]);

        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $autoPermission->id,
            'enabled' => true,
            'meta' => ['auto_granted' => true],
            'status' => 'granted',
            'granted_at' => now(),
        ]);
        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $manualPermission->id,
            'enabled' => true,
            'meta' => ['auto_granted' => false],
            'status' => 'granted',
            'granted_at' => now(),
        ]);

        $mockExecutor = Mockery::mock(HrEventTriggerExecutor::class);
        $mockExecutor->shouldReceive('execute')->once()->with($user->id, 'fire')->andReturn(true);
        $this->app->instance(HrEventTriggerExecutor::class, $mockExecutor);

        app(FireVirtualUserAction::class)->handle($user->id);

        $user->refresh();

        $this->assertSame(VirtualUserStatus::DEACTIVATED, $user->status);
        $this->assertDatabaseMissing('virtual_user_positions', [
            'virtual_user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('virtual_user_groups', [
            'virtual_user_id' => $user->id,
        ]);

        Queue::assertPushed(RevokeMultiplePermissionsJob::class, function (RevokeMultiplePermissionsJob $job) use ($autoPermission, $manualPermission) {
            $permissionIds = $this->readPrivateProperty($job, 'permissionIds');

            return in_array($autoPermission->id, $permissionIds, true)
                && !in_array($manualPermission->id, $permissionIds, true);
        });
    }

    private function readPrivateProperty(object $instance, string $property): mixed
    {
        $reflection = new \ReflectionClass($instance);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);

        return $prop->getValue($instance);
    }
}
