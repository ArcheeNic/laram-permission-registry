<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Services;

use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionDependency;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use ArcheeNic\PermissionRegistry\Services\PermissionDependencyResolver;
use ArcheeNic\PermissionRegistry\Tests\TestCase;

class PermissionDependencyResolverTest extends TestCase
{
    private PermissionDependencyResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new PermissionDependencyResolver;
    }

    public function test_validate_dependencies_valid_when_no_dependencies(): void
    {
        $user = VirtualUser::create(['name' => 'Test', 'status' => VirtualUserStatus::ACTIVE]);
        $permission = Permission::create(['service' => 'test', 'name' => 'no-deps']);

        $result = $this->resolver->validatePermissionDependencies($user->id, $permission);

        $this->assertTrue($result->isValid);
    }

    public function test_validate_dependencies_valid_when_strict_dependency_fulfilled(): void
    {
        $user = VirtualUser::create(['name' => 'Test', 'status' => VirtualUserStatus::ACTIVE]);
        $base = Permission::create(['service' => 'test', 'name' => 'base-perm']);
        $dependent = Permission::create(['service' => 'test', 'name' => 'dependent-perm']);

        PermissionDependency::create([
            'permission_id' => $dependent->id,
            'required_permission_id' => $base->id,
            'is_strict' => true,
            'event_type' => 'grant',
        ]);

        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $base->id,
            'enabled' => true,
            'status' => 'granted',
        ]);

        $result = $this->resolver->validatePermissionDependencies($user->id, $dependent, 'grant');

        $this->assertTrue($result->isValid);
    }

    public function test_validate_dependencies_invalid_when_strict_dependency_not_fulfilled(): void
    {
        $user = VirtualUser::create(['name' => 'Test', 'status' => VirtualUserStatus::ACTIVE]);
        $base = Permission::create(['service' => 'test', 'name' => 'base-perm']);
        $dependent = Permission::create(['service' => 'test', 'name' => 'dependent-perm']);

        PermissionDependency::create([
            'permission_id' => $dependent->id,
            'required_permission_id' => $base->id,
            'is_strict' => true,
            'event_type' => 'grant',
        ]);

        $result = $this->resolver->validatePermissionDependencies($user->id, $dependent, 'grant');

        $this->assertFalse($result->isValid);
        $this->assertNotEmpty($result->missingPermissions);
        $this->assertEquals($base->id, $result->missingPermissions[0]['id']);
    }

    public function test_validate_global_fields_valid_when_all_filled(): void
    {
        $user = VirtualUser::create(['name' => 'Test', 'status' => VirtualUserStatus::ACTIVE]);
        $permission = Permission::create(['service' => 'test', 'name' => 'perm']);
        $field = PermissionField::create([
            'name' => 'Email',
            'is_global' => true,
            'required_on_user_create' => false,
        ]);
        $permission->fields()->attach($field->id);

        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $field->id,
            'value' => 'test@example.com',
        ]);

        $result = $this->resolver->validateGlobalFields($user->id, $permission);

        $this->assertTrue($result->isValid);
    }

    public function test_validate_global_fields_invalid_when_field_not_filled(): void
    {
        $user = VirtualUser::create(['name' => 'Test', 'status' => VirtualUserStatus::ACTIVE]);
        $permission = Permission::create(['service' => 'test', 'name' => 'perm']);
        $field = PermissionField::create([
            'name' => 'Email',
            'is_global' => true,
            'required_on_user_create' => false,
        ]);
        $permission->fields()->attach($field->id);

        $result = $this->resolver->validateGlobalFields($user->id, $permission);

        $this->assertFalse($result->isValid);
        $this->assertNotEmpty($result->missingFields);
        $this->assertEquals($field->id, $result->missingFields[0]['id']);
    }

    public function test_validate_global_fields_with_values_valid_when_value_provided(): void
    {
        $user = VirtualUser::create(['name' => 'Test', 'status' => VirtualUserStatus::ACTIVE]);
        $permission = Permission::create(['service' => 'test', 'name' => 'perm']);
        $field = PermissionField::create([
            'name' => 'Email',
            'is_global' => true,
            'required_on_user_create' => false,
        ]);
        $permission->fields()->attach($field->id);

        $result = $this->resolver->validateGlobalFieldsWithValues(
            $user->id,
            $permission,
            [$field->id => 'test@example.com']
        );

        $this->assertTrue($result->isValid);
    }

    public function test_sort_by_dependencies_grant_order(): void
    {
        $a = Permission::create(['service' => 'test', 'name' => 'perm-a']);
        $b = Permission::create(['service' => 'test', 'name' => 'perm-b']);
        $c = Permission::create(['service' => 'test', 'name' => 'perm-c']);

        PermissionDependency::create([
            'permission_id' => $b->id,
            'required_permission_id' => $a->id,
            'is_strict' => true,
            'event_type' => 'grant',
        ]);
        PermissionDependency::create([
            'permission_id' => $c->id,
            'required_permission_id' => $b->id,
            'is_strict' => true,
            'event_type' => 'grant',
        ]);

        $result = $this->resolver->sortByDependencies([$a->id, $b->id, $c->id], 'grant');

        $this->assertSame([$a->id, $b->id, $c->id], $result);
    }

    public function test_sort_by_dependencies_revoke_order(): void
    {
        $a = Permission::create(['service' => 'test', 'name' => 'perm-a']);
        $b = Permission::create(['service' => 'test', 'name' => 'perm-b']);
        $c = Permission::create(['service' => 'test', 'name' => 'perm-c']);

        PermissionDependency::create([
            'permission_id' => $b->id,
            'required_permission_id' => $a->id,
            'is_strict' => true,
            'event_type' => 'revoke',
        ]);
        PermissionDependency::create([
            'permission_id' => $c->id,
            'required_permission_id' => $b->id,
            'is_strict' => true,
            'event_type' => 'revoke',
        ]);

        $result = $this->resolver->sortByDependencies([$a->id, $b->id, $c->id], 'revoke');

        $this->assertSame([$c->id, $b->id, $a->id], $result);
    }

    public function test_sort_by_dependencies_throws_on_circular(): void
    {
        $a = Permission::create(['service' => 'test', 'name' => 'perm-a']);
        $b = Permission::create(['service' => 'test', 'name' => 'perm-b']);

        PermissionDependency::create([
            'permission_id' => $a->id,
            'required_permission_id' => $b->id,
            'is_strict' => true,
            'event_type' => 'grant',
        ]);
        PermissionDependency::create([
            'permission_id' => $b->id,
            'required_permission_id' => $a->id,
            'is_strict' => true,
            'event_type' => 'grant',
        ]);

        $this->expectException(\RuntimeException::class);

        $this->resolver->sortByDependencies([$a->id, $b->id], 'grant');
    }

    public function test_can_grant_permission_true_when_all_ok(): void
    {
        $user = VirtualUser::create(['name' => 'Test', 'status' => VirtualUserStatus::ACTIVE]);
        $permission = Permission::create(['service' => 'test', 'name' => 'perm']);

        $this->assertTrue($this->resolver->canGrantPermission($user->id, $permission));
    }

    public function test_can_grant_permission_false_when_dependency_missing(): void
    {
        $user = VirtualUser::create(['name' => 'Test', 'status' => VirtualUserStatus::ACTIVE]);
        $base = Permission::create(['service' => 'test', 'name' => 'base']);
        $dependent = Permission::create(['service' => 'test', 'name' => 'dep']);

        PermissionDependency::create([
            'permission_id' => $dependent->id,
            'required_permission_id' => $base->id,
            'is_strict' => true,
            'event_type' => 'grant',
        ]);

        $this->assertFalse($this->resolver->canGrantPermission($user->id, $dependent));
    }
}
