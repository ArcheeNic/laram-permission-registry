<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\GenerateDisplayNameAction;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Facades\Config;

class GenerateDisplayNameActionTest extends TestCase
{
    public function test_generates_name_from_template_with_field_values(): void
    {
        $field1 = PermissionField::create(['name' => 'first_name']);
        $field2 = PermissionField::create(['name' => 'last_name']);
        Config::set('permission-registry.display_name_template', "{{$field1->id}} {{$field2->id}}");

        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
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

        $action = new GenerateDisplayNameAction();
        $result = $action->execute($user->id);

        $this->assertSame('John Doe', $result);
    }

    public function test_removes_unfilled_placeholders(): void
    {
        $field1 = PermissionField::create(['name' => 'first_name']);
        $field2 = PermissionField::create(['name' => 'last_name']);
        Config::set('permission-registry.display_name_template', "{{$field1->id}} {{$field2->id}}");

        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $field1->id,
            'value' => 'John',
        ]);

        $action = new GenerateDisplayNameAction();
        $result = $action->execute($user->id);

        $this->assertSame('John', $result);
    }

    public function test_returns_fallback_when_no_fields(): void
    {
        Config::set('permission-registry.display_name_template', '{1} {2}');

        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);

        $action = new GenerateDisplayNameAction();
        $result = $action->execute($user->id);

        $this->assertSame('User #' . $user->id, $result);
    }
}
