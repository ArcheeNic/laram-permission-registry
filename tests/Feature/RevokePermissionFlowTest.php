<?php

namespace ArcheeNic\PermissionRegistry\Tests\Feature;

use ArcheeNic\PermissionRegistry\Actions\GrantPermissionAction;
use ArcheeNic\PermissionRegistry\Actions\RevokePermissionAction;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Services\PermissionDependencyResolver;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use ArcheeNic\PermissionRegistry\ValueObjects\DependencyValidationResult;
use Mockery;

class RevokePermissionFlowTest extends TestCase
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

    public function test_revoke_with_skip_triggers_deletes_the_granted_permission(): void
    {
        $permission = Permission::create(['service' => 'test', 'name' => 'access', 'description' => 'Test']);
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);

        $grantAction = app(GrantPermissionAction::class);
        $grantAction->handle($user->id, $permission->id, skipTriggers: true);

        $this->assertDatabaseHas('granted_permissions', [
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
        ]);

        $revokeAction = app(RevokePermissionAction::class);
        $result = $revokeAction->handle($user->id, $permission->id, skipTriggers: true);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('granted_permissions', [
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
        ]);
    }

    public function test_revoke_non_existent_permission_returns_false(): void
    {
        $permission = Permission::create(['service' => 'test', 'name' => 'access', 'description' => 'Test']);
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);

        $revokeAction = app(RevokePermissionAction::class);
        $result = $revokeAction->handle($user->id, $permission->id, skipTriggers: true);

        $this->assertFalse($result);
    }
}
