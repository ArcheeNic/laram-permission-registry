<?php

namespace ArcheeNic\PermissionRegistry\DataTransferObjects;

class BulkOperationResult
{
    /**
     * @param array<int> $successVirtualUserIds
     * @param array<int> $skippedVirtualUserIds
     * @param array<int, array{virtual_user_id:int, message:string}> $failures
     */
    public function __construct(
        public array $successVirtualUserIds = [],
        public array $skippedVirtualUserIds = [],
        public array $failures = [],
    ) {
    }

    public function addSuccess(int $virtualUserId): void
    {
        $this->successVirtualUserIds[] = $virtualUserId;
    }

    public function addSkipped(int $virtualUserId): void
    {
        $this->skippedVirtualUserIds[] = $virtualUserId;
    }

    public function addFailure(int $virtualUserId, string $message): void
    {
        $this->failures[] = [
            'virtual_user_id' => $virtualUserId,
            'message' => $message,
        ];
    }

    public function successCount(): int
    {
        return count($this->successVirtualUserIds);
    }

    public function skippedCount(): int
    {
        return count($this->skippedVirtualUserIds);
    }

    public function failedCount(): int
    {
        return count($this->failures);
    }

    public function hasFailures(): bool
    {
        return $this->failedCount() > 0;
    }

    /**
     * @return array{
     *     success_virtual_user_ids: array<int>,
     *     skipped_virtual_user_ids: array<int>,
     *     failures: array<int, array{virtual_user_id:int, message:string}>
     * }
     */
    public function toArray(): array
    {
        return [
            'success_virtual_user_ids' => $this->successVirtualUserIds,
            'skipped_virtual_user_ids' => $this->skippedVirtualUserIds,
            'failures' => $this->failures,
        ];
    }
}
