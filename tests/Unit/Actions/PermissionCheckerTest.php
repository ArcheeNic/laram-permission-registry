<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\PermissionChecker;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Tests\TestCase;

class PermissionCheckerTest extends TestCase
{
    public function test_returns_true_when_permissions_table_empty(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);

        $checker = new PermissionChecker();
        $result = $checker->hasPermission($user->id, 'test', 'non-existent-perm');

        $this->assertTrue($result);
    }

    public function test_returns_true_when_specific_permission_not_registered(): void
    {
        Permission::create([
            'service' => 'other',
            'name' => 'other-perm',
            'description' => 'Other',
        ]);
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);

        $checker = new PermissionChecker();
        $result = $checker->hasPermission($user->id, 'test', 'non-existent-perm');

        $this->assertTrue($result);
    }

    public function test_returns_false_when_user_has_no_granted_permission(): void
    {
        Permission::create([
            'service' => 'test',
            'name' => 'test-perm',
            'description' => 'Test',
        ]);
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);

        $checker = new PermissionChecker();
        $result = $checker->hasPermission($user->id, 'test', 'test-perm');

        $this->assertFalse($result);
    }

    public function test_returns_true_when_user_has_valid_granted_permission(): void
    {
        $permission = Permission::create([
            'service' => 'test',
            'name' => 'test-perm',
            'description' => 'Test',
        ]);
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
            'enabled' => true,
            'granted_at' => now(),
        ]);

        $checker = new PermissionChecker();
        $result = $checker->hasPermission($user->id, 'test', 'test-perm');

        $this->assertTrue($result);
    }

    public function test_returns_false_when_granted_permission_is_disabled(): void
    {
        $permission = Permission::create([
            'service' => 'test',
            'name' => 'test-perm',
            'description' => 'Test',
        ]);
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
            'enabled' => false,
            'granted_at' => now(),
        ]);

        $checker = new PermissionChecker();
        $result = $checker->hasPermission($user->id, 'test', 'test-perm');

        $this->assertFalse($result);
    }

    public function test_returns_false_when_granted_permission_is_expired(): void
    {
        $permission = Permission::create([
            'service' => 'test',
            'name' => 'test-perm',
            'description' => 'Test',
        ]);
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
            'enabled' => true,
            'granted_at' => now()->subDays(10),
            'expires_at' => now()->subDay(),
        ]);

        $checker = new PermissionChecker();
        $result = $checker->hasPermission($user->id, 'test', 'test-perm');

        $this->assertFalse($result);
    }
}
