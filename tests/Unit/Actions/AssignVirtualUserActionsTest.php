<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\AssignVirtualUserGroupAction;
use ArcheeNic\PermissionRegistry\Actions\AssignVirtualUserPositionAction;
use ArcheeNic\PermissionRegistry\Actions\ReconcileUserPermissionsAction;
use ArcheeNic\PermissionRegistry\Events\VirtualUserGroupChanged;
use ArcheeNic\PermissionRegistry\Events\VirtualUserPositionChanged;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Models\VirtualUserGroup;
use ArcheeNic\PermissionRegistry\Models\VirtualUserPosition;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Mockery;

class AssignVirtualUserActionsTest extends TestCase
{
    private VirtualUser $user;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        $this->user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // --- AssignVirtualUserPositionAction ---

    public function test_position_handle_creates_pivot_record(): void
    {
        $position = Position::create(['name' => 'Developer']);
        $mockReconcile = Mockery::mock(ReconcileUserPermissionsAction::class);
        $mockReconcile->shouldReceive('handle')->once()->with($this->user->id);
        $this->app->instance(ReconcileUserPermissionsAction::class, $mockReconcile);
        $action = app(AssignVirtualUserPositionAction::class);

        $result = $action->handle($this->user->id, $position->id);

        $this->assertInstanceOf(VirtualUserPosition::class, $result);
        $this->assertDatabaseHas('virtual_user_positions', [
            'virtual_user_id' => $this->user->id,
            'position_id' => $position->id,
        ]);
    }

    public function test_position_handle_returns_existing_on_duplicate(): void
    {
        $position = Position::create(['name' => 'Developer']);
        $mockReconcile = Mockery::mock(ReconcileUserPermissionsAction::class);
        $mockReconcile->shouldReceive('handle')->twice()->with($this->user->id);
        $this->app->instance(ReconcileUserPermissionsAction::class, $mockReconcile);
        $action = app(AssignVirtualUserPositionAction::class);

        $first = $action->handle($this->user->id, $position->id);
        $second = $action->handle($this->user->id, $position->id);

        $this->assertEquals($first->id, $second->id);
        $this->assertDatabaseCount('virtual_user_positions', 1);
    }

    public function test_position_handle_dispatches_event(): void
    {
        $position = Position::create(['name' => 'Developer']);
        $mockReconcile = Mockery::mock(ReconcileUserPermissionsAction::class);
        $mockReconcile->shouldReceive('handle')->once()->with($this->user->id);
        $this->app->instance(ReconcileUserPermissionsAction::class, $mockReconcile);
        $action = app(AssignVirtualUserPositionAction::class);

        $action->handle($this->user->id, $position->id);

        Event::assertDispatched(VirtualUserPositionChanged::class, function ($event) use ($position) {
            return $event->userId === $this->user->id
                && $event->positionId === $position->id
                && $event->oldPositionId === null;
        });
    }

    public function test_position_handle_does_not_dispatch_event_on_duplicate(): void
    {
        $position = Position::create(['name' => 'Developer']);
        $mockReconcile = Mockery::mock(ReconcileUserPermissionsAction::class);
        $mockReconcile->shouldReceive('handle')->twice()->with($this->user->id);
        $this->app->instance(ReconcileUserPermissionsAction::class, $mockReconcile);
        $action = app(AssignVirtualUserPositionAction::class);

        $action->handle($this->user->id, $position->id);
        Event::fake(); // reset
        $action->handle($this->user->id, $position->id);

        Event::assertNotDispatched(VirtualUserPositionChanged::class);
    }

    public function test_position_remove_deletes_pivot_record(): void
    {
        $position = Position::create(['name' => 'Developer']);
        $mockReconcile = Mockery::mock(ReconcileUserPermissionsAction::class);
        $mockReconcile->shouldReceive('handle')->once()->with($this->user->id);
        $this->app->instance(ReconcileUserPermissionsAction::class, $mockReconcile);
        $action = app(AssignVirtualUserPositionAction::class);
        $action->handle($this->user->id, $position->id);
        Event::fake();

        $mockReconcile = Mockery::mock(ReconcileUserPermissionsAction::class);
        $mockReconcile->shouldReceive('handle')->once()->with($this->user->id);
        $this->app->instance(ReconcileUserPermissionsAction::class, $mockReconcile);
        $action = app(AssignVirtualUserPositionAction::class);
        $result = $action->remove($this->user->id, $position->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('virtual_user_positions', [
            'virtual_user_id' => $this->user->id,
            'position_id' => $position->id,
        ]);
    }

    public function test_position_remove_returns_false_when_not_found(): void
    {
        $mockReconcile = Mockery::mock(ReconcileUserPermissionsAction::class);
        $mockReconcile->shouldNotReceive('handle');
        $this->app->instance(ReconcileUserPermissionsAction::class, $mockReconcile);
        $action = app(AssignVirtualUserPositionAction::class);

        $result = $action->remove($this->user->id, 9999);

        $this->assertFalse($result);
    }

    public function test_position_remove_dispatches_event(): void
    {
        $position = Position::create(['name' => 'Developer']);
        $mockReconcile = Mockery::mock(ReconcileUserPermissionsAction::class);
        $mockReconcile->shouldReceive('handle')->once()->with($this->user->id);
        $this->app->instance(ReconcileUserPermissionsAction::class, $mockReconcile);
        $action = app(AssignVirtualUserPositionAction::class);
        $action->handle($this->user->id, $position->id);
        Event::fake();

        $mockReconcile = Mockery::mock(ReconcileUserPermissionsAction::class);
        $mockReconcile->shouldReceive('handle')->once()->with($this->user->id);
        $this->app->instance(ReconcileUserPermissionsAction::class, $mockReconcile);
        $action = app(AssignVirtualUserPositionAction::class);
        $action->remove($this->user->id, $position->id);

        Event::assertDispatched(VirtualUserPositionChanged::class, function ($event) use ($position) {
            return $event->userId === $this->user->id
                && $event->positionId === 0
                && $event->oldPositionId === $position->id;
        });
    }

    // --- AssignVirtualUserGroupAction ---

    public function test_group_handle_creates_pivot_record(): void
    {
        $group = PermissionGroup::create(['name' => 'Admins']);
        $action = new AssignVirtualUserGroupAction;

        $result = $action->handle($this->user->id, $group->id);

        $this->assertInstanceOf(VirtualUserGroup::class, $result);
        $this->assertDatabaseHas('virtual_user_groups', [
            'virtual_user_id' => $this->user->id,
            'permission_group_id' => $group->id,
        ]);
    }

    public function test_group_handle_dispatches_event(): void
    {
        $group = PermissionGroup::create(['name' => 'Admins']);
        $action = new AssignVirtualUserGroupAction;

        $action->handle($this->user->id, $group->id);

        Event::assertDispatched(VirtualUserGroupChanged::class, function ($event) use ($group) {
            return $event->userId === $this->user->id
                && $event->groupId === $group->id
                && $event->added === true;
        });
    }

    public function test_group_remove_deletes_pivot_record(): void
    {
        $group = PermissionGroup::create(['name' => 'Admins']);
        $action = new AssignVirtualUserGroupAction;
        $action->handle($this->user->id, $group->id);
        Event::fake();

        $result = $action->remove($this->user->id, $group->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('virtual_user_groups', [
            'virtual_user_id' => $this->user->id,
            'permission_group_id' => $group->id,
        ]);
    }

    public function test_group_remove_returns_false_when_not_found(): void
    {
        $action = new AssignVirtualUserGroupAction;

        $result = $action->remove($this->user->id, 9999);

        $this->assertFalse($result);
    }

    public function test_group_remove_dispatches_event(): void
    {
        $group = PermissionGroup::create(['name' => 'Admins']);
        $action = new AssignVirtualUserGroupAction;
        $action->handle($this->user->id, $group->id);
        Event::fake();

        $action->remove($this->user->id, $group->id);

        Event::assertDispatched(VirtualUserGroupChanged::class, function ($event) use ($group) {
            return $event->userId === $this->user->id
                && $event->groupId === $group->id
                && $event->added === false;
        });
    }
}
