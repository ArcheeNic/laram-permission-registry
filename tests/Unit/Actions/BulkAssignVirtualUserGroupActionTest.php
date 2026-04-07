<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\AssignVirtualUserGroupAction;
use ArcheeNic\PermissionRegistry\Actions\BulkAssignVirtualUserGroupAction;
use ArcheeNic\PermissionRegistry\DataTransferObjects\BulkOperationResult;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Models\VirtualUserGroup;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Database\QueryException;
use Mockery;

class BulkAssignVirtualUserGroupActionTest extends TestCase
{
    private function duplicateGroupAssignmentException(): QueryException
    {
        return new QueryException(
            'sqlite',
            'insert into virtual_user_groups',
            [],
            new \PDOException('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed', 23000)
        );
    }

    public function test_success_duplicate_skipped_exception_failed_processing_continues(): void
    {
        $group = PermissionGroup::create(['name' => 'Bulk G']);
        $u1 = VirtualUser::create(['name' => 'U1', 'status' => VirtualUserStatus::ACTIVE]);
        $u2 = VirtualUser::create(['name' => 'U2', 'status' => VirtualUserStatus::ACTIVE]);
        $u3 = VirtualUser::create(['name' => 'U3', 'status' => VirtualUserStatus::ACTIVE]);
        $u4 = VirtualUser::create(['name' => 'U4', 'status' => VirtualUserStatus::ACTIVE]);

        $pivot1 = VirtualUserGroup::create([
            'virtual_user_id' => $u1->id,
            'permission_group_id' => $group->id,
        ]);
        $pivot4 = VirtualUserGroup::create([
            'virtual_user_id' => $u4->id,
            'permission_group_id' => $group->id,
        ]);

        $assign = Mockery::mock(AssignVirtualUserGroupAction::class);
        $assign->shouldReceive('handle')
            ->once()
            ->with($u1->id, $group->id)
            ->andReturn($pivot1);
        $assign->shouldReceive('handle')
            ->once()
            ->with($u2->id, $group->id)
            ->andThrow($this->duplicateGroupAssignmentException());
        $assign->shouldReceive('handle')
            ->once()
            ->with($u3->id, $group->id)
            ->andThrow(new \RuntimeException('group assign failed'));
        $assign->shouldReceive('handle')
            ->once()
            ->with($u4->id, $group->id)
            ->andReturn($pivot4);
        $this->app->instance(AssignVirtualUserGroupAction::class, $assign);

        /** @var BulkAssignVirtualUserGroupAction $bulk */
        $bulk = app(BulkAssignVirtualUserGroupAction::class);
        $result = $bulk->handle([$u1->id, $u2->id, $u3->id, $u4->id], $group->id);

        $this->assertInstanceOf(BulkOperationResult::class, $result);
        $this->assertEqualsCanonicalizing([$u1->id, $u4->id], $result->successVirtualUserIds);
        $this->assertSame([$u2->id], $result->skippedVirtualUserIds);
        $this->assertCount(1, $result->failures);
        $this->assertSame($u3->id, $result->failures[0]['virtual_user_id']);
        $this->assertSame('Failed to assign group', $result->failures[0]['message']);
    }
}
