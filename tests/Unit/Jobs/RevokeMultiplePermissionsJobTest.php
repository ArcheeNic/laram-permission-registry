<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Jobs;

use ArcheeNic\PermissionRegistry\Jobs\RevokeMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Services\PermissionDependencyResolver;
use ArcheeNic\PermissionRegistry\Services\PermissionTriggerExecutor;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;

class RevokeMultiplePermissionsJobTest extends TestCase
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

    private function createGrantedPermission(int $userId, int $permissionId): GrantedPermission
    {
        return GrantedPermission::create([
            'virtual_user_id' => $userId,
            'permission_id' => $permissionId,
            'status' => 'granted',
            'enabled' => true,
        ]);
    }

    public function test_revokes_all_permissions(): void
    {
        $user = VirtualUser::create(['name' => 'Test', 'status' => VirtualUserStatus::ACTIVE]);
        $perm1 = Permission::create(['service' => 'test', 'name' => 'perm-1']);
        $perm2 = Permission::create(['service' => 'test', 'name' => 'perm-2']);

        $this->createGrantedPermission($user->id, $perm1->id);
        $this->createGrantedPermission($user->id, $perm2->id);

        $ids = [$perm1->id, $perm2->id];

        $mockResolver = Mockery::mock(PermissionDependencyResolver::class);
        $mockResolver->shouldReceive('sortByDependencies')->andReturn($ids);
        $this->app->instance(PermissionDependencyResolver::class, $mockResolver);

        $mockExecutor = Mockery::mock(PermissionTriggerExecutor::class);
        $mockExecutor->shouldReceive('executeChain')->andReturn(true);
        $this->app->instance(PermissionTriggerExecutor::class, $mockExecutor);

        $job = new RevokeMultiplePermissionsJob($user->id, $ids);
        $this->app->call([$job, 'handle']);

        $this->assertDatabaseMissing('granted_permissions', [
            'virtual_user_id' => $user->id,
            'permission_id' => $perm1->id,
        ]);
        $this->assertDatabaseMissing('granted_permissions', [
            'virtual_user_id' => $user->id,
            'permission_id' => $perm2->id,
        ]);
    }

    public function test_continues_revoking_when_one_fails(): void
    {
        $user = VirtualUser::create(['name' => 'Test', 'status' => VirtualUserStatus::ACTIVE]);
        $perm1 = Permission::create(['service' => 'test', 'name' => 'perm-ok']);
        $permFail = Permission::create(['service' => 'test', 'name' => 'perm-fail']);

        $this->createGrantedPermission($user->id, $perm1->id);
        $this->createGrantedPermission($user->id, $permFail->id);

        $ids = [$permFail->id, $perm1->id];

        $mockResolver = Mockery::mock(PermissionDependencyResolver::class);
        $mockResolver->shouldReceive('sortByDependencies')->andReturn($ids);
        $this->app->instance(PermissionDependencyResolver::class, $mockResolver);

        $mockExecutor = Mockery::mock(PermissionTriggerExecutor::class);
        $mockExecutor->shouldReceive('executeChain')
            ->andReturnUsing(function ($grantedPermission) use ($permFail) {
                if ($grantedPermission->permission_id === $permFail->id) {
                    throw new \RuntimeException('Trigger failed');
                }

                return true;
            });
        $this->app->instance(PermissionTriggerExecutor::class, $mockExecutor);

        $job = new RevokeMultiplePermissionsJob($user->id, $ids);
        $this->app->call([$job, 'handle']);

        $this->assertDatabaseHas('granted_permissions', [
            'virtual_user_id' => $user->id,
            'permission_id' => $permFail->id,
            'status' => 'failed',
        ]);
        $this->assertDatabaseMissing('granted_permissions', [
            'virtual_user_id' => $user->id,
            'permission_id' => $perm1->id,
        ]);
    }
}
