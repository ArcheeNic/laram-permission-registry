<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\SearchDuplicateFieldValuesAction;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use ArcheeNic\PermissionRegistry\Tests\TestCase;

class SearchDuplicateFieldValuesActionTest extends TestCase
{
    private PermissionField $nameField;

    private SearchDuplicateFieldValuesAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nameField = PermissionField::create([
            'name' => 'Имя',
            'is_global' => true,
            'required_on_user_create' => true,
        ]);

        $this->action = app(SearchDuplicateFieldValuesAction::class);
    }

    public function test_returns_zero_when_no_matches(): void
    {
        $this->assertSame(0, $this->action->execute($this->nameField->id, 'Anna'));
    }

    public function test_finds_exact_match_case_insensitive(): void
    {
        $user = VirtualUser::factory()->create();
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $this->nameField->id,
            'value' => 'Anna',
        ]);

        $this->assertSame(1, $this->action->execute($this->nameField->id, 'anna'));
        $this->assertSame(1, $this->action->execute($this->nameField->id, 'ANNA'));
        $this->assertSame(1, $this->action->execute($this->nameField->id, 'Anna'));
    }

    public function test_finds_prefix_match(): void
    {
        $user = VirtualUser::factory()->create();
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $this->nameField->id,
            'value' => 'Anna',
        ]);

        $this->assertSame(1, $this->action->execute($this->nameField->id, 'An'));
    }

    public function test_counts_distinct_users(): void
    {
        $user1 = VirtualUser::factory()->create();
        $user2 = VirtualUser::factory()->create();

        VirtualUserFieldValue::create([
            'virtual_user_id' => $user1->id,
            'permission_field_id' => $this->nameField->id,
            'value' => 'Anna',
        ]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user2->id,
            'permission_field_id' => $this->nameField->id,
            'value' => 'anna',
        ]);

        $this->assertSame(2, $this->action->execute($this->nameField->id, 'Anna'));
    }

    public function test_does_not_count_other_fields(): void
    {
        $otherField = PermissionField::create([
            'name' => 'Фамилия',
            'is_global' => true,
            'required_on_user_create' => true,
        ]);

        $user = VirtualUser::factory()->create();
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $otherField->id,
            'value' => 'Anna',
        ]);

        $this->assertSame(0, $this->action->execute($this->nameField->id, 'Anna'));
    }

    public function test_returns_zero_for_empty_value(): void
    {
        $this->assertSame(0, $this->action->execute($this->nameField->id, ''));
        $this->assertSame(0, $this->action->execute($this->nameField->id, '  '));
    }

    public function test_escapes_sql_wildcards(): void
    {
        $user = VirtualUser::factory()->create();
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $this->nameField->id,
            'value' => 'Test%Value',
        ]);

        $this->assertSame(1, $this->action->execute($this->nameField->id, 'Test%'));
        $this->assertSame(0, $this->action->execute($this->nameField->id, 'TestX'));
    }

    public function test_does_not_match_substring_in_middle(): void
    {
        $user = VirtualUser::factory()->create();
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $this->nameField->id,
            'value' => 'Anna',
        ]);

        $this->assertSame(0, $this->action->execute($this->nameField->id, 'nna'));
    }
}
