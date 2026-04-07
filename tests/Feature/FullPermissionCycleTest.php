<?php

namespace ArcheeNic\PermissionRegistry\Tests\Feature;

use ArcheeNic\PermissionRegistry\Actions\AssignVirtualUserGroupAction;
use ArcheeNic\PermissionRegistry\Actions\AssignVirtualUserPositionAction;
use ArcheeNic\PermissionRegistry\Actions\CreateVirtualUserAction;
use ArcheeNic\PermissionRegistry\Actions\GrantPermissionAction;
use ArcheeNic\PermissionRegistry\Actions\RevokePermissionAction;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Events\VirtualUserGroupChanged;
use ArcheeNic\PermissionRegistry\Events\VirtualUserPositionChanged;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionDependency;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use ArcheeNic\PermissionRegistry\Models\VirtualUserGroup;
use ArcheeNic\PermissionRegistry\Models\VirtualUserPosition;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

class FullPermissionCycleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['permission-registry.display_name_template' => '{1} {2}']);
    }

    public function test_create_user_with_global_fields_saves_and_generates_display_name(): void
    {
        $field1 = PermissionField::create([
            'name' => 'first_name',
            'default_value' => null,
            'is_global' => true,
            'required_on_user_create' => true,
        ]);
        $field2 = PermissionField::create([
            'name' => 'last_name',
            'default_value' => null,
            'is_global' => true,
            'required_on_user_create' => true,
        ]);

        config([
            'permission-registry.display_name_template' => "{{$field1->id}} {{$field2->id}}",
        ]);

        $action = app(CreateVirtualUserAction::class);
        $user = $action->handle([
            $field1->id => 'John',
            $field2->id => 'Doe',
        ]);

        $this->assertDatabaseHas('virtual_user_field_values', [
            'virtual_user_id' => $user->id,
            'permission_field_id' => $field1->id,
            'value' => 'John',
        ]);
        $this->assertDatabaseHas('virtual_user_field_values', [
            'virtual_user_id' => $user->id,
            'permission_field_id' => $field2->id,
            'value' => 'Doe',
        ]);
        $this->assertSame('John Doe', $user->name);
    }

    public function test_assign_position_creates_pivot_and_dispatches_event(): void
    {
        Event::fake([VirtualUserPositionChanged::class]);

        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $position = Position::create(['name' => 'Manager', 'description' => 'Manager position']);

        $action = app(AssignVirtualUserPositionAction::class);
        $userPosition = $action->handle($user->id, $position->id);

        $this->assertInstanceOf(VirtualUserPosition::class, $userPosition);
        $this->assertDatabaseHas('virtual_user_positions', [
            'virtual_user_id' => $user->id,
            'position_id' => $position->id,
        ]);

        Event::assertDispatched(VirtualUserPositionChanged::class, function ($event) use ($user, $position) {
            return $event->userId === $user->id && $event->positionId === $position->id;
        });
    }

    public function test_assign_group_creates_pivot_and_dispatches_event(): void
    {
        Event::fake([VirtualUserGroupChanged::class]);

        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $group = PermissionGroup::create(['name' => 'Developers', 'description' => 'Dev group']);

        $action = app(AssignVirtualUserGroupAction::class);
        $userGroup = $action->handle($user->id, $group->id);

        $this->assertInstanceOf(VirtualUserGroup::class, $userGroup);
        $this->assertDatabaseHas('virtual_user_groups', [
            'virtual_user_id' => $user->id,
            'permission_group_id' => $group->id,
        ]);

        Event::assertDispatched(VirtualUserGroupChanged::class, function ($event) use ($user, $group) {
            return $event->userId === $user->id
                && $event->groupId === $group->id
                && $event->added === true;
        });
    }

    public function test_grant_permission_skip_triggers_does_not_overwrite_global_fields(): void
    {
        $field = PermissionField::create([
            'name' => 'email',
            'default_value' => null,
            'is_global' => true,
            'required_on_user_create' => false,
        ]);

        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);

        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $field->id,
            'value' => 'original@example.com',
        ]);

        $permission = Permission::create(['service' => 'test', 'name' => 'access', 'description' => 'Test']);
        $permission->fields()->attach($field->id);

        $action = app(GrantPermissionAction::class);
        $granted = $action->handle(
            userId: $user->id,
            permissionId: $permission->id,
            fieldValues: [],
            skipTriggers: true,
        );

        $this->assertDatabaseHas('granted_permissions', [
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
            'status' => 'granted',
        ]);
        $this->assertInstanceOf(GrantedPermission::class, $granted);

        $this->assertDatabaseHas('virtual_user_field_values', [
            'virtual_user_id' => $user->id,
            'permission_field_id' => $field->id,
            'value' => 'original@example.com',
        ]);
    }

    public function test_grant_dependent_permission_fails_without_required_dependency(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);

        $requiredPermission = Permission::create([
            'service' => 'test',
            'name' => 'base_access',
            'description' => 'Base',
        ]);

        $dependentPermission = Permission::create([
            'service' => 'test',
            'name' => 'advanced_access',
            'description' => 'Advanced',
        ]);

        PermissionDependency::create([
            'permission_id' => $dependentPermission->id,
            'required_permission_id' => $requiredPermission->id,
            'is_strict' => true,
            'event_type' => 'grant',
        ]);

        $this->expectException(ValidationException::class);

        $action = app(GrantPermissionAction::class);
        $action->handle(
            userId: $user->id,
            permissionId: $dependentPermission->id,
            skipTriggers: true,
        );
    }

    public function test_grant_dependent_permission_succeeds_when_dependency_fulfilled(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);

        $requiredPermission = Permission::create([
            'service' => 'test',
            'name' => 'base_access',
            'description' => 'Base',
        ]);

        $dependentPermission = Permission::create([
            'service' => 'test',
            'name' => 'advanced_access',
            'description' => 'Advanced',
        ]);

        PermissionDependency::create([
            'permission_id' => $dependentPermission->id,
            'required_permission_id' => $requiredPermission->id,
            'is_strict' => true,
            'event_type' => 'grant',
        ]);

        $action = app(GrantPermissionAction::class);
        $action->handle(
            userId: $user->id,
            permissionId: $requiredPermission->id,
            skipTriggers: true,
        );

        $granted = $action->handle(
            userId: $user->id,
            permissionId: $dependentPermission->id,
            skipTriggers: true,
        );

        $this->assertDatabaseHas('granted_permissions', [
            'virtual_user_id' => $user->id,
            'permission_id' => $dependentPermission->id,
            'status' => 'granted',
        ]);
    }

    public function test_remove_position_deletes_pivot(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $position = Position::create(['name' => 'Manager', 'description' => 'Manager']);

        $action = app(AssignVirtualUserPositionAction::class);
        $action->handle($user->id, $position->id);

        $this->assertDatabaseHas('virtual_user_positions', [
            'virtual_user_id' => $user->id,
            'position_id' => $position->id,
        ]);

        $result = $action->remove($user->id, $position->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('virtual_user_positions', [
            'virtual_user_id' => $user->id,
            'position_id' => $position->id,
        ]);
    }

    public function test_remove_group_deletes_pivot(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $group = PermissionGroup::create(['name' => 'Developers', 'description' => 'Dev']);

        $action = app(AssignVirtualUserGroupAction::class);
        $action->handle($user->id, $group->id);

        $this->assertDatabaseHas('virtual_user_groups', [
            'virtual_user_id' => $user->id,
            'permission_group_id' => $group->id,
        ]);

        $result = $action->remove($user->id, $group->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('virtual_user_groups', [
            'virtual_user_id' => $user->id,
            'permission_group_id' => $group->id,
        ]);
    }

    public function test_revoke_permission_skip_triggers_deletes_record(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $permission = Permission::create(['service' => 'test', 'name' => 'access', 'description' => 'Test']);

        $grantAction = app(GrantPermissionAction::class);
        $grantAction->handle(
            userId: $user->id,
            permissionId: $permission->id,
            skipTriggers: true,
        );

        $this->assertDatabaseHas('granted_permissions', [
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
        ]);

        $revokeAction = app(RevokePermissionAction::class);
        $result = $revokeAction->handle(
            userId: $user->id,
            permissionId: $permission->id,
            skipTriggers: true,
        );

        $this->assertTrue($result);
        $this->assertDatabaseMissing('granted_permissions', [
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
        ]);
    }

    public function test_full_end_to_end_cycle(): void
    {
        $firstNameField = PermissionField::create([
            'name' => 'first_name',
            'default_value' => null,
            'is_global' => true,
            'required_on_user_create' => true,
        ]);
        $lastNameField = PermissionField::create([
            'name' => 'last_name',
            'default_value' => null,
            'is_global' => true,
            'required_on_user_create' => true,
        ]);

        config([
            'permission-registry.display_name_template' => "{{$firstNameField->id}} {{$lastNameField->id}}",
        ]);

        $createAction = app(CreateVirtualUserAction::class);
        $user = $createAction->handle([
            $firstNameField->id => 'Alice',
            $lastNameField->id => 'Smith',
        ]);

        $this->assertSame('Alice Smith', $user->name);

        $user->update(['status' => VirtualUserStatus::ACTIVE]);

        $group = PermissionGroup::create(['name' => 'Staff', 'description' => 'Staff']);
        $groupAction = app(AssignVirtualUserGroupAction::class);
        $groupAction->handle($user->id, $group->id);

        $this->assertDatabaseHas('virtual_user_groups', [
            'virtual_user_id' => $user->id,
            'permission_group_id' => $group->id,
        ]);

        $permission = Permission::create([
            'service' => 'test',
            'name' => 'system_access',
            'description' => 'System access',
        ]);
        $permission->fields()->attach([$firstNameField->id, $lastNameField->id]);

        $grantAction = app(GrantPermissionAction::class);
        $grantAction->handle(
            userId: $user->id,
            permissionId: $permission->id,
            fieldValues: [],
            skipTriggers: true,
        );

        $this->assertDatabaseHas('granted_permissions', [
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
            'status' => 'granted',
        ]);

        $this->assertDatabaseHas('virtual_user_field_values', [
            'virtual_user_id' => $user->id,
            'permission_field_id' => $firstNameField->id,
            'value' => 'Alice',
        ]);
        $this->assertDatabaseHas('virtual_user_field_values', [
            'virtual_user_id' => $user->id,
            'permission_field_id' => $lastNameField->id,
            'value' => 'Smith',
        ]);

        $revokeAction = app(RevokePermissionAction::class);
        $result = $revokeAction->handle(
            userId: $user->id,
            permissionId: $permission->id,
            skipTriggers: true,
        );

        $this->assertTrue($result);
        $this->assertDatabaseMissing('granted_permissions', [
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
        ]);

        $this->assertDatabaseHas('virtual_user_field_values', [
            'virtual_user_id' => $user->id,
            'permission_field_id' => $firstNameField->id,
            'value' => 'Alice',
        ]);
        $this->assertDatabaseHas('virtual_user_field_values', [
            'virtual_user_id' => $user->id,
            'permission_field_id' => $lastNameField->id,
            'value' => 'Smith',
        ]);
    }
}
