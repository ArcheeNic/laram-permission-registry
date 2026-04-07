<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\GrantPermissionAction;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use ArcheeNic\PermissionRegistry\Services\PermissionDependencyResolver;
use ArcheeNic\PermissionRegistry\Services\PermissionTriggerExecutor;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use ArcheeNic\PermissionRegistry\ValueObjects\DependencyValidationResult;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;

class GrantPermissionSaveFieldsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Queue::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeAction(): GrantPermissionAction
    {
        $mockResolver = Mockery::mock(PermissionDependencyResolver::class);
        $mockResolver->shouldReceive('validatePermissionDependencies')
            ->andReturn(DependencyValidationResult::valid());
        $this->app->instance(PermissionDependencyResolver::class, $mockResolver);

        $mockExecutor = Mockery::mock(PermissionTriggerExecutor::class);
        $this->app->instance(PermissionTriggerExecutor::class, $mockExecutor);

        return app(GrantPermissionAction::class);
    }

    public function test_save_fields_does_not_overwrite_existing_global_field_with_null(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $permission = Permission::create(['service' => 'test', 'name' => 'test-perm']);

        $field = PermissionField::create([
            'name' => 'Имя',
            'is_global' => true,
            'required_on_user_create' => false,
        ]);
        $permission->fields()->attach($field->id);

        VirtualUserFieldValue::create([
            VirtualUserFieldValue::VIRTUAL_USER_ID => $user->id,
            VirtualUserFieldValue::PERMISSION_FIELD_ID => $field->id,
            VirtualUserFieldValue::VALUE => 'Иван',
        ]);

        $action = $this->makeAction();
        $action->handle($user->id, $permission->id, [], [], null, true);

        $this->assertDatabaseHas('virtual_user_field_values', [
            'virtual_user_id' => $user->id,
            'permission_field_id' => $field->id,
            'value' => 'Иван',
        ]);
    }

    public function test_save_fields_updates_global_field_when_value_provided(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $permission = Permission::create(['service' => 'test', 'name' => 'test-perm']);

        $field = PermissionField::create([
            'name' => 'Имя',
            'is_global' => true,
            'required_on_user_create' => false,
        ]);
        $permission->fields()->attach($field->id);

        VirtualUserFieldValue::create([
            VirtualUserFieldValue::VIRTUAL_USER_ID => $user->id,
            VirtualUserFieldValue::PERMISSION_FIELD_ID => $field->id,
            VirtualUserFieldValue::VALUE => 'Иван',
        ]);

        $action = $this->makeAction();
        $action->handle($user->id, $permission->id, [$field->id => 'Пётр'], [], null, true);

        $this->assertDatabaseHas('virtual_user_field_values', [
            'virtual_user_id' => $user->id,
            'permission_field_id' => $field->id,
            'value' => 'Пётр',
        ]);
    }

    public function test_save_fields_creates_global_field_when_not_exists_and_value_provided(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $permission = Permission::create(['service' => 'test', 'name' => 'test-perm']);

        $field = PermissionField::create([
            'name' => 'Email',
            'is_global' => true,
            'required_on_user_create' => false,
        ]);
        $permission->fields()->attach($field->id);

        $action = $this->makeAction();
        $action->handle($user->id, $permission->id, [$field->id => 'test@test.com'], [], null, true);

        $this->assertDatabaseHas('virtual_user_field_values', [
            'virtual_user_id' => $user->id,
            'permission_field_id' => $field->id,
            'value' => 'test@test.com',
        ]);
    }

    public function test_save_fields_preserves_multiple_global_fields_when_only_some_passed(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $permission = Permission::create(['service' => 'test', 'name' => 'test-perm']);

        $fieldName = PermissionField::create([
            'name' => 'Имя',
            'is_global' => true,
            'required_on_user_create' => true,
        ]);
        $fieldEmail = PermissionField::create([
            'name' => 'Email',
            'is_global' => true,
            'required_on_user_create' => false,
        ]);
        $permission->fields()->attach([$fieldName->id, $fieldEmail->id]);

        VirtualUserFieldValue::create([
            VirtualUserFieldValue::VIRTUAL_USER_ID => $user->id,
            VirtualUserFieldValue::PERMISSION_FIELD_ID => $fieldName->id,
            VirtualUserFieldValue::VALUE => 'Иван',
        ]);
        VirtualUserFieldValue::create([
            VirtualUserFieldValue::VIRTUAL_USER_ID => $user->id,
            VirtualUserFieldValue::PERMISSION_FIELD_ID => $fieldEmail->id,
            VirtualUserFieldValue::VALUE => 'ivan@test.com',
        ]);

        $action = $this->makeAction();
        $action->handle($user->id, $permission->id, [$fieldName->id => 'Пётр'], [], null, true);

        $this->assertDatabaseHas('virtual_user_field_values', [
            'virtual_user_id' => $user->id,
            'permission_field_id' => $fieldName->id,
            'value' => 'Пётр',
        ]);
        $this->assertDatabaseHas('virtual_user_field_values', [
            'virtual_user_id' => $user->id,
            'permission_field_id' => $fieldEmail->id,
            'value' => 'ivan@test.com',
        ]);
    }
}
