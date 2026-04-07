<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit;

use ArcheeNic\PermissionRegistry\Actions\CheckPermissionFieldsAction;
use ArcheeNic\PermissionRegistry\Actions\GetUserPermissionsAction;
use ArcheeNic\PermissionRegistry\Actions\GrantPermissionAction;
use ArcheeNic\PermissionRegistry\Actions\PermissionChecker;
use ArcheeNic\PermissionRegistry\Actions\RevokePermissionAction;
use ArcheeNic\PermissionRegistry\Actions\SyncUserPermissionsAction;
use ArcheeNic\PermissionRegistry\PermissionRegistryManager;
use Mockery;
use PHPUnit\Framework\TestCase;

class PermissionRegistryManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_has_permission_delegates_to_permission_checker(): void
    {
        $permissionChecker = Mockery::mock(PermissionChecker::class);
        $permissionChecker->shouldReceive('hasPermission')
            ->once()
            ->with(1, 'service', 'permission')
            ->andReturn(true);

        $manager = new PermissionRegistryManager(
            $permissionChecker,
            Mockery::mock(GrantPermissionAction::class),
            Mockery::mock(RevokePermissionAction::class),
            Mockery::mock(GetUserPermissionsAction::class),
            Mockery::mock(CheckPermissionFieldsAction::class),
            Mockery::mock(SyncUserPermissionsAction::class)
        );

        $result = $manager->hasPermission(1, 'service', 'permission');

        $this->assertTrue($result);
    }

    public function test_get_user_permissions_delegates_to_get_user_permissions_action(): void
    {
        $expectedPermissions = [['name' => 'test', 'service' => 'svc']];
        $getUserPermissionsAction = Mockery::mock(GetUserPermissionsAction::class);
        $getUserPermissionsAction->shouldReceive('handle')
            ->once()
            ->with(1, null)
            ->andReturn($expectedPermissions);

        $manager = new PermissionRegistryManager(
            Mockery::mock(PermissionChecker::class),
            Mockery::mock(GrantPermissionAction::class),
            Mockery::mock(RevokePermissionAction::class),
            $getUserPermissionsAction,
            Mockery::mock(CheckPermissionFieldsAction::class),
            Mockery::mock(SyncUserPermissionsAction::class)
        );

        $result = $manager->getUserPermissions(1);

        $this->assertSame($expectedPermissions, $result);
    }

    public function test_get_user_permissions_with_service_delegates_correctly(): void
    {
        $expectedPermissions = [];
        $getUserPermissionsAction = Mockery::mock(GetUserPermissionsAction::class);
        $getUserPermissionsAction->shouldReceive('handle')
            ->once()
            ->with(5, 'bitrix24')
            ->andReturn($expectedPermissions);

        $manager = new PermissionRegistryManager(
            Mockery::mock(PermissionChecker::class),
            Mockery::mock(GrantPermissionAction::class),
            Mockery::mock(RevokePermissionAction::class),
            $getUserPermissionsAction,
            Mockery::mock(CheckPermissionFieldsAction::class),
            Mockery::mock(SyncUserPermissionsAction::class)
        );

        $result = $manager->getUserPermissions(5, 'bitrix24');

        $this->assertSame($expectedPermissions, $result);
    }

    public function test_validate_field_delegates_to_check_permission_fields_action(): void
    {
        $checkPermissionFieldsAction = Mockery::mock(CheckPermissionFieldsAction::class);
        $checkPermissionFieldsAction->shouldReceive('validate')
            ->once()
            ->with(1, 'svc', 'perm', 'field', 'value')
            ->andReturn(true);

        $manager = new PermissionRegistryManager(
            Mockery::mock(PermissionChecker::class),
            Mockery::mock(GrantPermissionAction::class),
            Mockery::mock(RevokePermissionAction::class),
            Mockery::mock(GetUserPermissionsAction::class),
            $checkPermissionFieldsAction,
            Mockery::mock(SyncUserPermissionsAction::class)
        );

        $result = $manager->validateField(1, 'svc', 'perm', 'field', 'value');

        $this->assertTrue($result);
    }

    public function test_sync_user_permissions_delegates_to_sync_user_permissions_action(): void
    {
        $syncUserPermissionsAction = Mockery::mock(SyncUserPermissionsAction::class);
        $syncUserPermissionsAction->shouldReceive('handle')
            ->once()
            ->with(42);

        $manager = new PermissionRegistryManager(
            Mockery::mock(PermissionChecker::class),
            Mockery::mock(GrantPermissionAction::class),
            Mockery::mock(RevokePermissionAction::class),
            Mockery::mock(GetUserPermissionsAction::class),
            Mockery::mock(CheckPermissionFieldsAction::class),
            $syncUserPermissionsAction
        );

        $manager->syncUserPermissions(42);

        $this->addToAssertionCount(1);
    }
}
