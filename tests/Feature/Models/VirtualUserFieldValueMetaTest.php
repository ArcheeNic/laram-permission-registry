<?php

namespace ArcheeNic\PermissionRegistry\Tests\Feature\Models;

use ArcheeNic\PermissionRegistry\Enums\PermissionFieldType;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use ArcheeNic\PermissionRegistry\Tests\TestCase;

class VirtualUserFieldValueMetaTest extends TestCase
{
    public function test_get_meta_returns_default_when_meta_is_null(): void
    {
        $value = $this->createValue();

        $this->assertTrue($value->getMeta('b24_sync', true));
        $this->assertNull($value->getMeta('missing'));
    }

    public function test_set_and_get_meta(): void
    {
        $value = $this->createValue();
        $value->setMeta('b24_sync', false);
        $value->save();

        $value->refresh();

        $this->assertFalse($value->getMeta('b24_sync'));
        $this->assertSame(['b24_sync' => false], $value->meta);
    }

    public function test_forget_meta_removes_key(): void
    {
        $value = $this->createValue();
        $value->setMeta('b24_sync', false);
        $value->setMeta('other', 'x');
        $value->save();
        $value->forgetMeta('b24_sync')->save();

        $value->refresh();

        $this->assertSame(['other' => 'x'], $value->meta);
    }

    public function test_forget_last_meta_nullifies(): void
    {
        $value = $this->createValue();
        $value->setMeta('b24_sync', false);
        $value->save();
        $value->forgetMeta('b24_sync')->save();

        $value->refresh();

        $this->assertNull($value->meta);
    }

    public function test_values_by_field_type_filters_by_permission_field_type(): void
    {
        $emailField = PermissionField::create([
            'name' => 'corp_email',
            'type' => PermissionFieldType::EMAIL->value,
            'is_global' => true,
        ]);
        $personalEmailField = PermissionField::create([
            'name' => 'personal_email',
            'type' => PermissionFieldType::EMAIL->value,
            'is_global' => true,
        ]);
        $phoneField = PermissionField::create([
            'name' => 'phone',
            'type' => PermissionFieldType::PHONE->value,
            'is_global' => true,
        ]);

        $user = VirtualUser::create(['name' => 'Alice', 'status' => VirtualUserStatus::ACTIVE]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $emailField->id,
            'value' => 'alice@corp.com',
        ]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $personalEmailField->id,
            'value' => 'alice@home.com',
        ]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $phoneField->id,
            'value' => '+79991234567',
        ]);

        $emails = $user->valuesByFieldType(PermissionFieldType::EMAIL);

        $this->assertCount(2, $emails);
        $this->assertEqualsCanonicalizing(
            ['alice@corp.com', 'alice@home.com'],
            $emails->pluck('value')->all(),
        );
    }

    private function createValue(): VirtualUserFieldValue
    {
        $field = PermissionField::create([
            'name' => 'email',
            'type' => PermissionFieldType::EMAIL->value,
            'is_global' => true,
        ]);
        $user = VirtualUser::create(['name' => 'Test', 'status' => VirtualUserStatus::ACTIVE]);

        return VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $field->id,
            'value' => 'test@example.com',
        ]);
    }
}
