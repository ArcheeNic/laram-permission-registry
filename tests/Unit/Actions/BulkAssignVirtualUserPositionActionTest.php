<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\AssignVirtualUserPositionAction;
use ArcheeNic\PermissionRegistry\Actions\BulkAssignVirtualUserPositionAction;
use ArcheeNic\PermissionRegistry\DataTransferObjects\BulkOperationResult;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Models\VirtualUserPosition;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Database\QueryException;
use Mockery;

class BulkAssignVirtualUserPositionActionTest extends TestCase
{
    private function duplicatePositionAssignmentException(): QueryException
    {
        return new QueryException(
            'sqlite',
            'insert into virtual_user_positions',
            [],
            new \PDOException('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed', 23000)
        );
    }

    public function test_success_duplicate_skipped_exception_failed_processing_continues(): void
    {
        $position = Position::create(['name' => 'Bulk P']);
        $u1 = VirtualUser::create(['name' => 'P1', 'status' => VirtualUserStatus::ACTIVE]);
        $u2 = VirtualUser::create(['name' => 'P2', 'status' => VirtualUserStatus::ACTIVE]);
        $u3 = VirtualUser::create(['name' => 'P3', 'status' => VirtualUserStatus::ACTIVE]);
        $u4 = VirtualUser::create(['name' => 'P4', 'status' => VirtualUserStatus::ACTIVE]);

        $pivot1 = VirtualUserPosition::create([
            'virtual_user_id' => $u1->id,
            'position_id' => $position->id,
        ]);
        $pivot4 = VirtualUserPosition::create([
            'virtual_user_id' => $u4->id,
            'position_id' => $position->id,
        ]);

        $assign = Mockery::mock(AssignVirtualUserPositionAction::class);
        $assign->shouldReceive('handle')
            ->once()
            ->with($u1->id, $position->id)
            ->andReturn($pivot1);
        $assign->shouldReceive('handle')
            ->once()
            ->with($u2->id, $position->id)
            ->andThrow($this->duplicatePositionAssignmentException());
        $assign->shouldReceive('handle')
            ->once()
            ->with($u3->id, $position->id)
            ->andThrow(new \RuntimeException('position assign failed'));
        $assign->shouldReceive('handle')
            ->once()
            ->with($u4->id, $position->id)
            ->andReturn($pivot4);
        $this->app->instance(AssignVirtualUserPositionAction::class, $assign);

        /** @var BulkAssignVirtualUserPositionAction $bulk */
        $bulk = app(BulkAssignVirtualUserPositionAction::class);
        $result = $bulk->handle([$u1->id, $u2->id, $u3->id, $u4->id], $position->id);

        $this->assertInstanceOf(BulkOperationResult::class, $result);
        $this->assertEqualsCanonicalizing([$u1->id, $u4->id], $result->successVirtualUserIds);
        $this->assertSame([$u2->id], $result->skippedVirtualUserIds);
        $this->assertCount(1, $result->failures);
        $this->assertSame($u3->id, $result->failures[0]['virtual_user_id']);
        $this->assertSame('Failed to assign position', $result->failures[0]['message']);
    }
}
