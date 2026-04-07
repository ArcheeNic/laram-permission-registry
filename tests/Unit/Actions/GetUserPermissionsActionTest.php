<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\GetUserPermissionsAction;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\GrantedPermissionFieldValue;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Tests\TestCase;

class GetUserPermissionsActionTest extends TestCase
{
    public function test_returns_empty_array_when_no_permissions(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);

        $action = new GetUserPermissionsAction();
        $result = $action->handle($user->id);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_returns_permissions_with_fields(): void
    {
        $permission = Permission::create([
            'service' => 'test',
            'name' => 'test-perm',
            'description' => 'Test permission',
        ]);
        $field = PermissionField::create(['name' => 'email']);
        $permission->fields()->attach($field->id);
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $granted = GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
            'enabled' => true,
            'granted_at' => now(),
        ]);
        GrantedPermissionFieldValue::create([
            'granted_permission_id' => $granted->id,
            'permission_field_id' => $field->id,
            'value' => 'user@test.com',
        ]);

        $action = new GetUserPermissionsAction();
        $result = $action->handle($user->id);

        $this->assertCount(1, $result);
        $this->assertSame($permission->id, $result[0]['id']);
        $this->assertSame('test-perm', $result[0]['name']);
        $this->assertSame('test', $result[0]['service']);
        $this->assertSame('Test permission', $result[0]['description']);
        $this->assertArrayHasKey('fields', $result[0]);
        $this->assertSame('user@test.com', $result[0]['fields']['email']);
        $this->assertArrayHasKey('granted_at', $result[0]);
        $this->assertNull($result[0]['expires_at']);
    }

    public function test_filters_by_service_when_provided(): void
    {
        $perm1 = Permission::create(['service' => 'service-a', 'name' => 'perm-a', 'description' => 'A']);
        $perm2 = Permission::create(['service' => 'service-b', 'name' => 'perm-b', 'description' => 'B']);
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $perm1->id,
            'enabled' => true,
            'granted_at' => now(),
        ]);
        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $perm2->id,
            'enabled' => true,
            'granted_at' => now(),
        ]);

        $action = new GetUserPermissionsAction();
        $result = $action->handle($user->id, 'service-a');

        $this->assertCount(1, $result);
        $this->assertSame('service-a', $result[0]['service']);
        $this->assertSame('perm-a', $result[0]['name']);
    }
}
