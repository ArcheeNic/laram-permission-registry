<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\GetVirtualUserMappedFieldsAction;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\PermissionTrigger;
use ArcheeNic\PermissionRegistry\Models\TriggerFieldMapping;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use ArcheeNic\PermissionRegistry\Tests\TestCase;

class GetVirtualUserMappedFieldsActionTest extends TestCase
{
    public function test_returns_empty_collection_when_no_mappings(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $trigger = PermissionTrigger::create([
            'name' => 'TestTrigger',
            'class_name' => 'App\Triggers\Test',
        ]);

        $action = app(GetVirtualUserMappedFieldsAction::class);
        $result = $action->execute($user->id, $trigger->id);

        $this->assertTrue($result->isEmpty());
    }

    public function test_returns_mapped_fields_with_values(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $trigger = PermissionTrigger::create([
            'name' => 'TestTrigger',
            'class_name' => 'App\Triggers\Test',
        ]);
        $field = PermissionField::create(['name' => 'email']);
        TriggerFieldMapping::create([
            'permission_trigger_id' => $trigger->id,
            'trigger_field_name' => 'email',
            'permission_field_id' => $field->id,
            'is_internal' => false,
        ]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $field->id,
            'value' => 'user@example.com',
        ]);

        $action = app(GetVirtualUserMappedFieldsAction::class);
        $result = $action->execute($user->id, $trigger->id);

        $this->assertFalse($result->isEmpty());
        $this->assertArrayHasKey('email', $result->toArray());
        $this->assertSame('user@example.com', $result['email']['value']);
        $this->assertSame($field->id, $result['email']['id']);
    }
}
