<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Jobs;

use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Jobs\GrantMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Services\PermissionDependencyResolver;
use ArcheeNic\PermissionRegistry\Services\PermissionTriggerExecutor;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use ArcheeNic\PermissionRegistry\ValueObjects\DependencyValidationResult;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;

class GrantMultiplePermissionsJobTest extends TestCase
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

    private function mockDependencies(array $sortedIds): void
    {
        $mockResolver = Mockery::mock(PermissionDependencyResolver::class);
        $mockResolver->shouldReceive('sortByDependencies')->andReturn($sortedIds);
        $mockResolver->shouldReceive('validatePermissionDependencies')
            ->andReturn(DependencyValidationResult::valid());
        $this->app->instance(PermissionDependencyResolver::class, $mockResolver);

        $mockExecutor = Mockery::mock(PermissionTriggerExecutor::class);
        $mockExecutor->shouldReceive('executeChain')->andReturn(true);
        $this->app->instance(PermissionTriggerExecutor::class, $mockExecutor);
    }

    public function test_grants_all_permissions_to_user(): void
    {
        $user = VirtualUser::create(['name' => 'Test', 'status' => VirtualUserStatus::ACTIVE]);
        $perm1 = Permission::create(['service' => 'test', 'name' => 'perm-1']);
        $perm2 = Permission::create(['service' => 'test', 'name' => 'perm-2']);
        $perm3 = Permission::create(['service' => 'test', 'name' => 'perm-3']);

        $ids = [$perm1->id, $perm2->id, $perm3->id];
        $this->mockDependencies($ids);

        $permissionsData = array_map(fn ($id) => ['permissionId' => $id], $ids);

        $job = new GrantMultiplePermissionsJob($user->id, $permissionsData);
        $this->app->call([$job, 'handle']);

        foreach ($ids as $id) {
            $this->assertDatabaseHas('granted_permissions', [
                'virtual_user_id' => $user->id,
                'permission_id' => $id,
            ]);
        }
    }

    public function test_saves_global_fields_when_granting(): void
    {
        $user = VirtualUser::create(['name' => 'Test', 'status' => VirtualUserStatus::ACTIVE]);
        $perm = Permission::create(['service' => 'test', 'name' => 'perm-fields']);

        $field = PermissionField::create([
            'name' => 'Email',
            'is_global' => true,
            'required_on_user_create' => false,
        ]);
        $perm->fields()->attach($field->id);

        $this->mockDependencies([$perm->id]);

        $permissionsData = [
            [
                'permissionId' => $perm->id,
                'fieldValues' => [$field->id => 'user@example.com'],
            ],
        ];

        $job = new GrantMultiplePermissionsJob($user->id, $permissionsData);
        $this->app->call([$job, 'handle']);

        $this->assertDatabaseHas('virtual_user_field_values', [
            'virtual_user_id' => $user->id,
            'permission_field_id' => $field->id,
            'value' => 'user@example.com',
        ]);
    }

    public function test_continues_granting_when_one_fails(): void
    {
        $user = VirtualUser::create(['name' => 'Test', 'status' => VirtualUserStatus::ACTIVE]);
        $perm1 = Permission::create(['service' => 'test', 'name' => 'perm-ok-1']);
        $permFail = Permission::create(['service' => 'test', 'name' => 'perm-fail']);
        $perm2 = Permission::create(['service' => 'test', 'name' => 'perm-ok-2']);

        $ids = [$perm1->id, $permFail->id, $perm2->id];

        $mockResolver = Mockery::mock(PermissionDependencyResolver::class);
        $mockResolver->shouldReceive('sortByDependencies')->andReturn($ids);
        $mockResolver->shouldReceive('validatePermissionDependencies')
            ->andReturn(DependencyValidationResult::valid());
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

        $permissionsData = array_map(fn ($id) => ['permissionId' => $id], $ids);

        $job = new GrantMultiplePermissionsJob($user->id, $permissionsData);
        $this->app->call([$job, 'handle']);

        $this->assertDatabaseHas('granted_permissions', [
            'virtual_user_id' => $user->id,
            'permission_id' => $perm1->id,
        ]);
        $this->assertDatabaseHas('granted_permissions', [
            'virtual_user_id' => $user->id,
            'permission_id' => $perm2->id,
        ]);
        $this->assertDatabaseHas('granted_permissions', [
            'virtual_user_id' => $user->id,
            'permission_id' => $permFail->id,
            'status' => 'failed',
        ]);
    }

    public function test_skips_all_grants_when_user_is_deactivated(): void
    {
        $user = VirtualUser::create([
            'name' => 'Deactivated',
            'status' => VirtualUserStatus::DEACTIVATED,
        ]);
        $perm = Permission::create(['service' => 'test', 'name' => 'perm-skip']);

        $permissionsData = [['permissionId' => $perm->id]];

        $job = new GrantMultiplePermissionsJob($user->id, $permissionsData);
        $this->app->call([$job, 'handle']);

        $this->assertDatabaseMissing('granted_permissions', [
            'virtual_user_id' => $user->id,
            'permission_id' => $perm->id,
        ]);
    }
}
