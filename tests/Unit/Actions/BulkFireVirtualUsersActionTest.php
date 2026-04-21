<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\BulkFireVirtualUsersAction;
use ArcheeNic\PermissionRegistry\Actions\FireVirtualUserAction;
use ArcheeNic\PermissionRegistry\DataTransferObjects\BulkOperationResult;
use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Mockery;

class BulkFireVirtualUsersActionTest extends TestCase
{
    public function test_active_fired_deactivated_skipped_exception_failed_others_continue(): void
    {
        $u1 = VirtualUser::create(['name' => 'A1', 'status' => VirtualUserStatus::ACTIVE, 'employee_category' => EmployeeCategory::STAFF]);
        $u2 = VirtualUser::create(['name' => 'D1', 'status' => VirtualUserStatus::DEACTIVATED, 'employee_category' => EmployeeCategory::STAFF]);
        $u3 = VirtualUser::create(['name' => 'A2', 'status' => VirtualUserStatus::ACTIVE, 'employee_category' => EmployeeCategory::STAFF]);
        $u4 = VirtualUser::create(['name' => 'A3', 'status' => VirtualUserStatus::ACTIVE, 'employee_category' => EmployeeCategory::STAFF]);

        $fire = Mockery::mock(FireVirtualUserAction::class);
        $fire->shouldReceive('handle')
            ->with($u2->id)
            ->never();
        $fire->shouldReceive('handle')
            ->once()
            ->with($u1->id)
            ->andReturnUsing(function () use ($u1) {
                $u1->update(['status' => VirtualUserStatus::DEACTIVATED]);

                return $u1->fresh();
            });
        $fire->shouldReceive('handle')
            ->once()
            ->with($u3->id)
            ->andThrow(new \RuntimeException('fire pipeline failed'));
        $fire->shouldReceive('handle')
            ->once()
            ->with($u4->id)
            ->andReturnUsing(function () use ($u4) {
                $u4->update(['status' => VirtualUserStatus::DEACTIVATED]);

                return $u4->fresh();
            });
        $this->app->instance(FireVirtualUserAction::class, $fire);

        /** @var BulkFireVirtualUsersAction $bulk */
        $bulk = app(BulkFireVirtualUsersAction::class);
        $result = $bulk->handle([$u1->id, $u2->id, $u3->id, $u4->id]);

        $this->assertInstanceOf(BulkOperationResult::class, $result);
        $this->assertEqualsCanonicalizing([$u1->id, $u4->id], $result->successVirtualUserIds);
        $this->assertSame([$u2->id], $result->skippedVirtualUserIds);
        $this->assertCount(1, $result->failures);
        $this->assertSame($u3->id, $result->failures[0]['virtual_user_id']);
        $this->assertSame('Failed to fire user', $result->failures[0]['message']);
    }

    public function test_empty_ids_returns_empty_result(): void
    {
        $fire = Mockery::mock(FireVirtualUserAction::class);
        $fire->shouldReceive('handle')->never();
        $this->app->instance(FireVirtualUserAction::class, $fire);

        /** @var BulkFireVirtualUsersAction $bulk */
        $bulk = app(BulkFireVirtualUsersAction::class);
        $result = $bulk->handle([]);

        $this->assertSame([], $result->successVirtualUserIds);
        $this->assertSame([], $result->skippedVirtualUserIds);
        $this->assertSame([], $result->failures);
    }
}
