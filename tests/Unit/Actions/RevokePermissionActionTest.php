<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\RevokePermissionAction;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Services\PermissionTriggerExecutor;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;

class RevokePermissionActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Queue::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_returns_false_when_no_granted_permission_exists(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $permission = Permission::create(['service' => 'test', 'name' => 'test-perm']);

        $mockExecutor = Mockery::mock(PermissionTriggerExecutor::class);
        $mockExecutor->shouldNotReceive('executeChain');
        $this->app->instance(PermissionTriggerExecutor::class, $mockExecutor);

        $action = app(RevokePermissionAction::class);
        $result = $action->handle($user->id, $permission->id, true);

        $this->assertFalse($result);
    }

    public function test_deletes_granted_permission_when_skip_triggers_true(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $permission = Permission::create(['service' => 'test', 'name' => 'test-perm']);
        $granted = GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
            'status' => 'granted',
        ]);

        $mockExecutor = Mockery::mock(PermissionTriggerExecutor::class);
        $mockExecutor->shouldNotReceive('executeChain');
        $this->app->instance(PermissionTriggerExecutor::class, $mockExecutor);

        $action = app(RevokePermissionAction::class);
        $result = $action->handle($user->id, $permission->id, true);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('granted_permissions', ['id' => $granted->id]);
    }

    public function test_sets_status_to_revoking_when_skip_triggers_false(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $permission = Permission::create(['service' => 'test', 'name' => 'test-perm']);
        $granted = GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
            'status' => 'granted',
        ]);

        $mockExecutor = Mockery::mock(PermissionTriggerExecutor::class);
        $mockExecutor->shouldNotReceive('executeChain');
        $this->app->instance(PermissionTriggerExecutor::class, $mockExecutor);

        $action = app(RevokePermissionAction::class);
        $action->handle($user->id, $permission->id, false, false);

        $granted->refresh();
        $this->assertSame('revoking', $granted->status);
    }

    public function test_force_deletes_broken_permission_without_revoke_triggers(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $permission = Permission::create(['service' => 'test', 'name' => 'broken-perm']);
        $granted = GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
            'status' => 'partially_granted',
            'enabled' => true,
        ]);

        $mockExecutor = Mockery::mock(PermissionTriggerExecutor::class);
        $mockExecutor->shouldNotReceive('executeChain');
        $this->app->instance(PermissionTriggerExecutor::class, $mockExecutor);

        $action = app(RevokePermissionAction::class);
        $result = $action->handle($user->id, $permission->id, false, true);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('granted_permissions', ['id' => $granted->id]);
    }
}
