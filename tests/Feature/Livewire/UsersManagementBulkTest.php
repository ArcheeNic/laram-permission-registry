<?php

namespace ArcheeNic\PermissionRegistry\Tests\Feature\Livewire;

use App\Models\User;
use ArcheeNic\PermissionRegistry\Actions\BulkAssignVirtualUserGroupAction;
use ArcheeNic\PermissionRegistry\Actions\BulkAssignVirtualUserPositionAction;
use ArcheeNic\PermissionRegistry\Actions\BulkFireVirtualUsersAction;
use ArcheeNic\PermissionRegistry\Actions\BulkHireVirtualUsersAction;
use ArcheeNic\PermissionRegistry\DataTransferObjects\BulkOperationResult;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Livewire\UsersManagement;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Mockery;

class UsersManagementBulkTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->withPersonalTeam()->create());
        Gate::define('permission-registry.manage', fn () => true);
    }

    public function test_toggle_bulk_select_adds_and_removes_user_ids(): void
    {
        $user = VirtualUser::create([
            'name' => 'Bulk Candidate',
            'status' => VirtualUserStatus::DEACTIVATED,
        ]);

        Livewire::test(UsersManagement::class)
            ->call('toggleBulkSelect', $user->id)
            ->assertSet('bulkSelectedIds', [$user->id])
            ->call('toggleBulkSelect', $user->id)
            ->assertSet('bulkSelectedIds', []);
    }

    public function test_bulk_hire_users_calls_action_and_clears_selection(): void
    {
        $u1 = VirtualUser::create(['name' => 'One', 'status' => VirtualUserStatus::DEACTIVATED]);
        $u2 = VirtualUser::create(['name' => 'Two', 'status' => VirtualUserStatus::DEACTIVATED]);

        $action = Mockery::mock(BulkHireVirtualUsersAction::class);
        $action->shouldReceive('handle')
            ->once()
            ->with([$u1->id, $u2->id], [], [], 'staff')
            ->andReturn(new BulkOperationResult(successVirtualUserIds: [$u1->id, $u2->id]));
        $this->app->instance(BulkHireVirtualUsersAction::class, $action);

        Livewire::test(UsersManagement::class)
            ->set('bulkSelectedIds', [$u1->id, $u2->id])
            ->set('selectedHireCategory', 'staff')
            ->call('bulkHireUsers')
            ->assertSet('bulkSelectedIds', []);
    }

    public function test_bulk_assign_group_calls_action_and_clears_selection(): void
    {
        $group = PermissionGroup::create(['name' => 'Ops']);
        $u1 = VirtualUser::create(['name' => 'One', 'status' => VirtualUserStatus::ACTIVE]);
        $u2 = VirtualUser::create(['name' => 'Two', 'status' => VirtualUserStatus::ACTIVE]);

        $action = Mockery::mock(BulkAssignVirtualUserGroupAction::class);
        $action->shouldReceive('handle')
            ->once()
            ->with([$u1->id, $u2->id], $group->id)
            ->andReturn(new BulkOperationResult(successVirtualUserIds: [$u1->id, $u2->id]));
        $this->app->instance(BulkAssignVirtualUserGroupAction::class, $action);

        Livewire::test(UsersManagement::class)
            ->set('bulkSelectedIds', [$u1->id, $u2->id])
            ->set('bulkGroupId', (string) $group->id)
            ->call('bulkAssignGroup')
            ->assertSet('bulkSelectedIds', []);
    }

    public function test_bulk_fire_users_calls_action_and_clears_selection(): void
    {
        $u1 = VirtualUser::create(['name' => 'One', 'status' => VirtualUserStatus::ACTIVE]);
        $u2 = VirtualUser::create(['name' => 'Two', 'status' => VirtualUserStatus::ACTIVE]);

        $action = Mockery::mock(BulkFireVirtualUsersAction::class);
        $action->shouldReceive('handle')
            ->once()
            ->with([$u1->id, $u2->id])
            ->andReturn(new BulkOperationResult(successVirtualUserIds: [$u1->id, $u2->id]));
        $this->app->instance(BulkFireVirtualUsersAction::class, $action);

        Livewire::test(UsersManagement::class)
            ->set('bulkSelectedIds', [$u1->id, $u2->id])
            ->call('bulkFireUsers')
            ->assertSet('bulkSelectedIds', []);
    }

    public function test_set_bulk_action_accepts_known_actions_and_rejects_others(): void
    {
        Livewire::test(UsersManagement::class)
            ->call('setBulkAction', 'hire')
            ->assertSet('bulkAction', 'hire')
            ->call('setBulkAction', 'fire')
            ->assertSet('bulkAction', 'fire')
            ->call('setBulkAction', 'unknown')
            ->assertSet('bulkAction', '');
    }

    public function test_clear_selection_resets_bulk_action(): void
    {
        Livewire::test(UsersManagement::class)
            ->set('bulkSelectedIds', [1, 2])
            ->set('bulkAction', 'hire')
            ->call('clearBulkSelection')
            ->assertSet('bulkSelectedIds', [])
            ->assertSet('bulkAction', '');
    }

    public function test_bulk_assign_position_calls_action_and_clears_selection(): void
    {
        $position = Position::create(['name' => 'QA']);
        $u1 = VirtualUser::create(['name' => 'One', 'status' => VirtualUserStatus::ACTIVE]);
        $u2 = VirtualUser::create(['name' => 'Two', 'status' => VirtualUserStatus::ACTIVE]);

        $action = Mockery::mock(BulkAssignVirtualUserPositionAction::class);
        $action->shouldReceive('handle')
            ->once()
            ->with([$u1->id, $u2->id], $position->id)
            ->andReturn(new BulkOperationResult(successVirtualUserIds: [$u1->id, $u2->id]));
        $this->app->instance(BulkAssignVirtualUserPositionAction::class, $action);

        Livewire::test(UsersManagement::class)
            ->set('bulkSelectedIds', [$u1->id, $u2->id])
            ->set('bulkPositionId', (string) $position->id)
            ->call('bulkAssignPosition')
            ->assertSet('bulkSelectedIds', []);
    }
}
