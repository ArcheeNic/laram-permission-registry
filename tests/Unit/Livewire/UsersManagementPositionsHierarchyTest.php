<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Livewire;

use App\Models\User;
use ArcheeNic\PermissionRegistry\Livewire\UsersManagement;
use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Livewire\Livewire;

class UsersManagementPositionsHierarchyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->withPersonalTeam()->create());
    }

    public function test_positions_section_shows_full_hierarchy_path_for_nested_assigned_position(): void
    {
        $head = Position::factory()->create(['name' => 'PR_Hierarchy_Head']);
        $lead = Position::factory()->create([
            'name' => 'PR_Hierarchy_Lead',
            'parent_id' => $head->id,
        ]);
        $developer = Position::factory()->create([
            'name' => 'PR_Hierarchy_Dev',
            'parent_id' => $lead->id,
        ]);

        $user = VirtualUser::factory()->create();
        $user->positions()->attach($developer->id);

        Livewire::test(UsersManagement::class)
            ->call('openEditModal', $user->id)
            ->assertSee('PR_Hierarchy_Head -> PR_Hierarchy_Lead -> PR_Hierarchy_Dev', escape: false);
    }

    public function test_positions_section_shows_only_name_for_root_position(): void
    {
        $solo = Position::factory()->create([
            'name' => 'PR_Root_Only_Label',
            'parent_id' => null,
        ]);

        $user = VirtualUser::factory()->create();
        $user->positions()->attach($solo->id);

        Livewire::test(UsersManagement::class)
            ->call('openEditModal', $user->id)
            ->assertSee('PR_Root_Only_Label', escape: false)
            ->assertDontSee('PR_Root_Only_Label ->', escape: false);
    }

    public function test_position_selector_shows_hierarchy_path_for_nested_position(): void
    {
        $head = Position::factory()->create(['name' => 'PR_Select_Head']);
        $lead = Position::factory()->create([
            'name' => 'PR_Select_Lead',
            'parent_id' => $head->id,
        ]);
        Position::factory()->create([
            'name' => 'PR_Select_Dev',
            'parent_id' => $lead->id,
        ]);

        $user = VirtualUser::factory()->create();

        Livewire::test(UsersManagement::class)
            ->call('openEditModal', $user->id)
            ->assertSee('PR_Select_Head -> PR_Select_Lead -> PR_Select_Dev');
    }
}
