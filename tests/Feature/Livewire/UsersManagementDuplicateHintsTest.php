<?php

namespace ArcheeNic\PermissionRegistry\Tests\Feature\Livewire;

use App\Models\User;
use ArcheeNic\PermissionRegistry\Livewire\UsersManagement;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Livewire\Livewire;

class UsersManagementDuplicateHintsTest extends TestCase
{
    private PermissionField $nameField;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $this->nameField = PermissionField::create([
            'name' => 'Name',
            'is_global' => true,
            'required_on_user_create' => true,
        ]);
    }

    public function test_duplicate_hints_populated_on_field_update(): void
    {
        $existing = VirtualUser::factory()->create();
        VirtualUserFieldValue::create([
            'virtual_user_id' => $existing->id,
            'permission_field_id' => $this->nameField->id,
            'value' => 'Anna',
        ]);

        Livewire::test(UsersManagement::class)
            ->call('toggleCreateForm')
            ->set('newUserFields.' . $this->nameField->id, 'Anna')
            ->assertSet('duplicateHints.' . $this->nameField->id, 1);
    }

    public function test_duplicate_hints_cleared_when_value_empty(): void
    {
        $existing = VirtualUser::factory()->create();
        VirtualUserFieldValue::create([
            'virtual_user_id' => $existing->id,
            'permission_field_id' => $this->nameField->id,
            'value' => 'Anna',
        ]);

        Livewire::test(UsersManagement::class)
            ->call('toggleCreateForm')
            ->set('newUserFields.' . $this->nameField->id, 'Anna')
            ->assertSet('duplicateHints.' . $this->nameField->id, 1)
            ->set('newUserFields.' . $this->nameField->id, '')
            ->assertSet('duplicateHints', []);
    }

    public function test_duplicate_hints_reset_on_toggle_form(): void
    {
        $existing = VirtualUser::factory()->create();
        VirtualUserFieldValue::create([
            'virtual_user_id' => $existing->id,
            'permission_field_id' => $this->nameField->id,
            'value' => 'Anna',
        ]);

        Livewire::test(UsersManagement::class)
            ->call('toggleCreateForm')
            ->set('newUserFields.' . $this->nameField->id, 'Anna')
            ->assertSet('duplicateHints.' . $this->nameField->id, 1)
            ->call('toggleCreateForm')
            ->assertSet('duplicateHints', []);
    }

    public function test_no_hints_when_no_duplicates(): void
    {
        Livewire::test(UsersManagement::class)
            ->call('toggleCreateForm')
            ->set('newUserFields.' . $this->nameField->id, 'UniqueNameXYZ')
            ->assertSet('duplicateHints', []);
    }
}
