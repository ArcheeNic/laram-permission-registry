<?php

namespace ArcheeNic\PermissionRegistry\Tests\Feature;

use ArcheeNic\PermissionRegistry\Actions\GrantPermissionAction;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\GrantedPermissionFieldValue;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Services\PermissionDependencyResolver;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use ArcheeNic\PermissionRegistry\ValueObjects\DependencyValidationResult;
use Mockery;

class GrantPermissionFlowTest extends TestCase
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

    public function test_full_grant_permission_flow(): void
    {
        $permission = Permission::create(['service' => 'test', 'name' => 'access', 'description' => 'Test']);
        $field = PermissionField::create(['name' => 'role', 'default_value' => 'user', 'is_global' => false]);
        $permission->fields()->attach($field->id);

        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);

        $action = app(GrantPermissionAction::class);
        $granted = $action->handle($user->id, $permission->id, skipTriggers: true);

        $this->assertDatabaseHas('granted_permissions', [
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
            'status' => 'granted',
            'enabled' => true,
        ]);
        $this->assertInstanceOf(GrantedPermission::class, $granted);
    }

    public function test_grant_permission_creates_record_and_field_values(): void
    {
        $permission = Permission::create(['service' => 'test', 'name' => 'access', 'description' => 'Test']);
        $field = PermissionField::create(['name' => 'role', 'default_value' => 'user', 'is_global' => false]);
        $permission->fields()->attach($field->id);

        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);

        $action = app(GrantPermissionAction::class);
        $action->handle($user->id, $permission->id, [ $field->id => 'admin' ], skipTriggers: true);

        $granted = GrantedPermission::where('virtual_user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->first();

        $this->assertNotNull($granted);
        $fieldValue = GrantedPermissionFieldValue::where('granted_permission_id', $granted->id)
            ->where('permission_field_id', $field->id)
            ->first();
        $this->assertNotNull($fieldValue);
        $this->assertSame('admin', $fieldValue->value);
    }

    public function test_grant_with_skip_triggers_sets_status_to_granted_immediately(): void
    {
        $permission = Permission::create(['service' => 'test', 'name' => 'access', 'description' => 'Test']);
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);

        $action = app(GrantPermissionAction::class);
        $granted = $action->handle($user->id, $permission->id, skipTriggers: true);

        $this->assertSame('granted', $granted->status);
    }

    public function test_grant_same_permission_twice_updates_existing_record(): void
    {
        $permission = Permission::create(['service' => 'test', 'name' => 'access', 'description' => 'Test']);
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);

        $action = app(GrantPermissionAction::class);
        $granted1 = $action->handle($user->id, $permission->id, [], ['meta' => 'first'], skipTriggers: true);
        $granted2 = $action->handle($user->id, $permission->id, [], ['meta' => 'second'], skipTriggers: true);

        $this->assertSame($granted1->id, $granted2->id);
        $this->assertSame(1, GrantedPermission::where('virtual_user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->count());
    }
}
