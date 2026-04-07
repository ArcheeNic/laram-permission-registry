<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\CheckPermissionFieldsAction;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\GrantedPermissionFieldValue;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Tests\TestCase;

class CheckPermissionFieldsActionTest extends TestCase
{
    public function test_returns_false_when_permission_does_not_exist(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);

        $action = new CheckPermissionFieldsAction();
        $result = $action->validate($user->id, 'test', 'non-existent', 'email', 'test@test.com');

        $this->assertFalse($result);
    }

    public function test_returns_false_when_field_does_not_exist(): void
    {
        $permission = Permission::create([
            'service' => 'test',
            'name' => 'test-perm',
            'description' => 'Test',
        ]);
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $granted = GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
            'enabled' => true,
            'granted_at' => now(),
        ]);
        $field = PermissionField::create(['name' => 'email']);
        $permission->fields()->attach($field->id);
        GrantedPermissionFieldValue::create([
            'granted_permission_id' => $granted->id,
            'permission_field_id' => $field->id,
            'value' => 'test@test.com',
        ]);

        $action = new CheckPermissionFieldsAction();
        $result = $action->validate($user->id, 'test', 'test-perm', 'non-existent-field', 'test@test.com');

        $this->assertFalse($result);
    }

    public function test_returns_false_when_no_matching_value(): void
    {
        $permission = Permission::create([
            'service' => 'test',
            'name' => 'test-perm',
            'description' => 'Test',
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
            'value' => 'other@test.com',
        ]);

        $action = new CheckPermissionFieldsAction();
        $result = $action->validate($user->id, 'test', 'test-perm', 'email', 'test@test.com');

        $this->assertFalse($result);
    }

    public function test_returns_true_when_matching_value_exists(): void
    {
        $permission = Permission::create([
            'service' => 'test',
            'name' => 'test-perm',
            'description' => 'Test',
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
            'value' => 'test@test.com',
        ]);

        $action = new CheckPermissionFieldsAction();
        $result = $action->validate($user->id, 'test', 'test-perm', 'email', 'test@test.com');

        $this->assertTrue($result);
    }
}
