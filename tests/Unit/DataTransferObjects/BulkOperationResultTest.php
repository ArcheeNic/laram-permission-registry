<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\DataTransferObjects;

use ArcheeNic\PermissionRegistry\DataTransferObjects\BulkOperationResult;
use PHPUnit\Framework\TestCase;

class BulkOperationResultTest extends TestCase
{
    public function test_holds_success_skipped_and_failure_entries(): void
    {
        $result = new BulkOperationResult(
            successVirtualUserIds: [10, 20],
            skippedVirtualUserIds: [30],
            failures: [
                ['virtual_user_id' => 40, 'message' => 'Simulated error'],
            ]
        );

        $this->assertSame([10, 20], $result->successVirtualUserIds);
        $this->assertSame([30], $result->skippedVirtualUserIds);
        $this->assertCount(1, $result->failures);
        $this->assertSame(40, $result->failures[0]['virtual_user_id']);
        $this->assertSame('Simulated error', $result->failures[0]['message']);
    }

    public function test_counts_match_array_lengths(): void
    {
        $result = new BulkOperationResult(
            successVirtualUserIds: [1, 2, 3],
            skippedVirtualUserIds: [4],
            failures: [
                ['virtual_user_id' => 5, 'message' => 'a'],
                ['virtual_user_id' => 6, 'message' => 'b'],
            ]
        );

        $this->assertSame(3, $result->successCount());
        $this->assertSame(1, $result->skippedCount());
        $this->assertSame(2, $result->failedCount());
    }

    public function test_empty_operation(): void
    {
        $result = new BulkOperationResult;

        $this->assertSame([], $result->successVirtualUserIds);
        $this->assertSame([], $result->skippedVirtualUserIds);
        $this->assertSame([], $result->failures);
        $this->assertSame(0, $result->successCount());
        $this->assertSame(0, $result->skippedCount());
        $this->assertSame(0, $result->failedCount());
    }
}
