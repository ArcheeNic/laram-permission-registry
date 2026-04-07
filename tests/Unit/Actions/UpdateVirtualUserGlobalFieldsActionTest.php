<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\GenerateDisplayNameAction;
use ArcheeNic\PermissionRegistry\Actions\GetVirtualUserFieldValueAction;
use ArcheeNic\PermissionRegistry\Actions\UpdateVirtualUserGlobalFieldsAction;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use ArcheeNic\PermissionRegistry\Tests\TestCase;

class UpdateVirtualUserGlobalFieldsActionTest extends TestCase
{
    public function test_updates_existing_field_values(): void
    {
        $user = VirtualUser::create(['name' => 'Old Name', 'status' => VirtualUserStatus::ACTIVE]);
        $field = PermissionField::create([
            'name' => 'first_name',
            'is_global' => true,
        ]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $field->id,
            'value' => 'OldValue',
        ]);

        config(['permission-registry.display_name_template' => "{{$field->id}}"]);

        $action = app(UpdateVirtualUserGlobalFieldsAction::class);
        $action->execute($user->id, [$field->id => 'NewValue']);

        $updated = VirtualUserFieldValue::where('virtual_user_id', $user->id)
            ->where('permission_field_id', $field->id)
            ->first();
        $this->assertSame('NewValue', $updated->value);
    }

    public function test_updates_user_display_name(): void
    {
        $field1 = PermissionField::create(['name' => 'first', 'is_global' => true]);
        $field2 = PermissionField::create(['name' => 'last', 'is_global' => true]);
        $user = VirtualUser::create(['name' => 'Initial', 'status' => VirtualUserStatus::ACTIVE]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $field1->id,
            'value' => 'John',
        ]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $field2->id,
            'value' => 'Doe',
        ]);

        config(['permission-registry.display_name_template' => "{{$field1->id}} {{$field2->id}}"]);

        $action = app(UpdateVirtualUserGlobalFieldsAction::class);
        $action->execute($user->id, [$field1->id => 'Jane', $field2->id => 'Smith']);

        $user->refresh();
        $this->assertSame('Jane Smith', $user->name);
    }
}
