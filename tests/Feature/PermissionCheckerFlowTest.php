<?php

namespace ArcheeNic\PermissionRegistry\Tests\Feature;

use ArcheeNic\PermissionRegistry\Actions\GrantPermissionAction;
use ArcheeNic\PermissionRegistry\Actions\PermissionChecker;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Services\PermissionDependencyResolver;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use ArcheeNic\PermissionRegistry\ValueObjects\DependencyValidationResult;
use Mockery;

class PermissionCheckerFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(
            PermissionDependencyResolver::class,
            Mockery::mock(PermissionDependencyResolver::class)
                ->shouldReceive('validatePermissionDependencies')
                ->andReturn(DependencyValidationResult::valid())
                ->getMock()
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_has_permission_returns_true_for_active_permission(): void
    {
        $permission = Permission::create(['service' => 'test', 'name' => 'access', 'description' => 'Test']);
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);

        $grantAction = app(GrantPermissionAction::class);
        $grantAction->handle($user->id, $permission->id, skipTriggers: true);

        $checker = app(PermissionChecker::class);
        $this->assertTrue($checker->hasPermission($user->id, 'test', 'access'));
    }

    public function test_has_permission_returns_false_for_expired_permission(): void
    {
        $permission = Permission::create(['service' => 'test', 'name' => 'access', 'description' => 'Test']);
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);

        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
            'status' => 'granted',
            'enabled' => true,
            'granted_at' => now()->subDays(10),
            'expires_at' => now()->subDay(),
        ]);

        $checker = app(PermissionChecker::class);
        $this->assertFalse($checker->hasPermission($user->id, 'test', 'access'));
    }

    public function test_has_permission_returns_false_for_disabled_permission(): void
    {
        $permission = Permission::create(['service' => 'test', 'name' => 'access', 'description' => 'Test']);
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);

        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
            'status' => 'granted',
            'enabled' => false,
            'granted_at' => now(),
        ]);

        $checker = app(PermissionChecker::class);
        $this->assertFalse($checker->hasPermission($user->id, 'test', 'access'));
    }
}
