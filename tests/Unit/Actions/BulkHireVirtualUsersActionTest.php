<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\BulkHireVirtualUsersAction;
use ArcheeNic\PermissionRegistry\Actions\HireVirtualUserAction;
use ArcheeNic\PermissionRegistry\DataTransferObjects\BulkOperationResult;
use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Mockery;

class BulkHireVirtualUsersActionTest extends TestCase
{
    public function test_deactivated_hired_active_skipped_exception_failed_others_continue(): void
    {
        $u1 = VirtualUser::create(['name' => 'D1', 'status' => VirtualUserStatus::DEACTIVATED, 'employee_category' => EmployeeCategory::STAFF]);
        $u2 = VirtualUser::create(['name' => 'A1', 'status' => VirtualUserStatus::ACTIVE, 'employee_category' => EmployeeCategory::STAFF]);
        $u3 = VirtualUser::create(['name' => 'D2', 'status' => VirtualUserStatus::DEACTIVATED, 'employee_category' => EmployeeCategory::STAFF]);
        $u4 = VirtualUser::create(['name' => 'D3', 'status' => VirtualUserStatus::DEACTIVATED, 'employee_category' => EmployeeCategory::STAFF]);

        $category = EmployeeCategory::CONTRACTOR;
        $positionIds = [7, 8];
        $groupIds = [9];

        $hire = Mockery::mock(HireVirtualUserAction::class);
        $hire->shouldReceive('handle')
            ->with($u2->id, Mockery::any(), Mockery::any(), Mockery::any())
            ->never();
        $hire->shouldReceive('handle')
            ->once()
            ->with($u1->id, $positionIds, $groupIds, $category)
            ->andReturnUsing(function () use ($u1) {
                $u1->update(['status' => VirtualUserStatus::ACTIVE]);

                return $u1->fresh();
            });
        $hire->shouldReceive('handle')
            ->once()
            ->with($u3->id, $positionIds, $groupIds, $category)
            ->andThrow(new \RuntimeException('hire pipeline failed'));
        $hire->shouldReceive('handle')
            ->once()
            ->with($u4->id, $positionIds, $groupIds, $category)
            ->andReturnUsing(function () use ($u4) {
                $u4->update(['status' => VirtualUserStatus::ACTIVE]);

                return $u4->fresh();
            });
        $this->app->instance(HireVirtualUserAction::class, $hire);

        /** @var BulkHireVirtualUsersAction $bulk */
        $bulk = app(BulkHireVirtualUsersAction::class);
        $result = $bulk->handle(
            [$u1->id, $u2->id, $u3->id, $u4->id],
            $positionIds,
            $groupIds,
            $category
        );

        $this->assertInstanceOf(BulkOperationResult::class, $result);
        $this->assertEqualsCanonicalizing([$u1->id, $u4->id], $result->successVirtualUserIds);
        $this->assertSame([$u2->id], $result->skippedVirtualUserIds);
        $this->assertCount(1, $result->failures);
        $this->assertSame($u3->id, $result->failures[0]['virtual_user_id']);
        $this->assertSame('Failed to hire user', $result->failures[0]['message']);

        $u2->refresh();
        $this->assertSame(VirtualUserStatus::ACTIVE, $u2->status);
    }
}
