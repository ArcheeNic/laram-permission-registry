<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Models;

use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrantedPermissionTest extends TestCase
{
    public function test_can_be_created_with_fillable_attributes(): void
    {
        $user = VirtualUser::create(['name' => 'Grant Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $permission = Permission::create([
            'service' => 'test',
            'name' => 'grant-test-perm',
        ]);

        $granted = GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
            'status' => 'granted',
            'enabled' => true,
        ]);

        $this->assertDatabaseHas('granted_permissions', [
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
        ]);
        $this->assertTrue($granted->enabled);
    }

    public function test_user_relationship(): void
    {
        $granted = new GrantedPermission();
        $this->assertInstanceOf(BelongsTo::class, $granted->user());
    }

    public function test_permission_relationship(): void
    {
        $granted = new GrantedPermission();
        $this->assertInstanceOf(BelongsTo::class, $granted->permission());
    }

    public function test_field_values_relationship(): void
    {
        $granted = new GrantedPermission();
        $this->assertInstanceOf(HasMany::class, $granted->fieldValues());
    }

    public function test_execution_logs_relationship(): void
    {
        $granted = new GrantedPermission();
        $this->assertInstanceOf(HasMany::class, $granted->executionLogs());
    }

    public function test_meta_cast_to_array(): void
    {
        $user = VirtualUser::create(['name' => 'Meta Grant User', 'status' => VirtualUserStatus::ACTIVE]);
        $permission = Permission::create(['service' => 'test', 'name' => 'meta-grant-perm']);

        $granted = GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
            'status' => 'granted',
            'meta' => ['extra' => 'data'],
        ]);

        $granted->refresh();
        $this->assertIsArray($granted->meta);
        $this->assertSame(['extra' => 'data'], $granted->meta);
    }

    public function test_enabled_cast_to_boolean(): void
    {
        $user = VirtualUser::create(['name' => 'Bool User', 'status' => VirtualUserStatus::ACTIVE]);
        $permission = Permission::create(['service' => 'test', 'name' => 'bool-perm']);

        $granted = GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
            'status' => 'granted',
            'enabled' => 1,
        ]);

        $granted->refresh();
        $this->assertTrue($granted->enabled);
    }

    public function test_granted_at_and_expires_at_cast_to_datetime(): void
    {
        $user = VirtualUser::create(['name' => 'Date User', 'status' => VirtualUserStatus::ACTIVE]);
        $permission = Permission::create(['service' => 'test', 'name' => 'date-perm']);

        $granted = GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
            'status' => 'granted',
        ]);

        $granted->refresh();
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $granted->granted_at);
    }
}
