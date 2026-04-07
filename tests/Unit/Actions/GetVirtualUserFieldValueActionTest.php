<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\GetVirtualUserFieldValueAction;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Collection;

class GetVirtualUserFieldValueActionTest extends TestCase
{
    public function test_execute_returns_null_when_no_value(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $field = PermissionField::create(['name' => 'email']);

        $action = new GetVirtualUserFieldValueAction();
        $result = $action->execute($user->id, $field->id);

        $this->assertNull($result);
    }

    public function test_execute_returns_value_when_exists(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $field = PermissionField::create(['name' => 'email']);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $field->id,
            'value' => 'user@test.com',
        ]);

        $action = new GetVirtualUserFieldValueAction();
        $result = $action->execute($user->id, $field->id);

        $this->assertSame('user@test.com', $result);
    }

    public function test_execute_all_returns_all_field_values(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $field1 = PermissionField::create(['name' => 'email']);
        $field2 = PermissionField::create(['name' => 'phone']);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $field1->id,
            'value' => 'user@test.com',
        ]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $field2->id,
            'value' => '+79001234567',
        ]);

        $action = new GetVirtualUserFieldValueAction();
        $result = $action->executeAll($user->id);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $values = $result->pluck('value')->toArray();
        $this->assertContains('user@test.com', $values);
        $this->assertContains('+79001234567', $values);
    }

    public function test_execute_all_returns_empty_collection_when_no_values(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);

        $action = new GetVirtualUserFieldValueAction();
        $result = $action->executeAll($user->id);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }
}
