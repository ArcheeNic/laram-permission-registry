<?php

namespace ArcheeNic\PermissionRegistry\Livewire\Concerns;

use ArcheeNic\PermissionRegistry\DataTransferObjects\BulkOperationResult;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;

trait ManagesBulkSelection
{
    /** @var array<int> */
    public array $bulkSelectedIds = [];

    public bool $bulkSelectAll = false;

    public string $bulkGroupId = '';

    public string $bulkPositionId = '';

    public bool $showBulkResultModal = false;

    /** @var array<int> */
    public array $bulkResultSuccessIds = [];

    /** @var array<int> */
    public array $bulkResultSkippedIds = [];

    /** @var array<int, array{virtual_user_id:int, message:string}> */
    public array $bulkResultFailures = [];

    public function toggleBulkSelect(int $virtualUserId): void
    {
        if (in_array($virtualUserId, $this->bulkSelectedIds, true)) {
            $this->bulkSelectedIds = array_values(
                array_filter(
                    $this->bulkSelectedIds,
                    static fn (int $id): bool => $id !== $virtualUserId
                )
            );
        } else {
            $this->bulkSelectedIds[] = $virtualUserId;
            $this->bulkSelectedIds = array_values(array_unique($this->bulkSelectedIds));
        }
    }

    /**
     * @param array<int> $currentPageIds
     */
    public function toggleBulkSelectAll(array $currentPageIds): void
    {
        $currentPageIds = array_values(array_unique(array_map('intval', $currentPageIds)));
        if ($currentPageIds === []) {
            return;
        }

        $allSelected = count(array_intersect($currentPageIds, $this->bulkSelectedIds)) === count($currentPageIds);
        if ($allSelected) {
            $this->bulkSelectedIds = array_values(array_diff($this->bulkSelectedIds, $currentPageIds));
            return;
        }

        $this->bulkSelectedIds = array_values(array_unique([...$this->bulkSelectedIds, ...$currentPageIds]));
    }

    public function clearBulkSelection(): void
    {
        $this->bulkSelectedIds = [];
        $this->bulkSelectAll = false;
    }

    public function closeBulkResultModal(): void
    {
        $this->showBulkResultModal = false;
    }

    public function getBulkSelectedCountProperty(): int
    {
        return count($this->bulkSelectedIds);
    }

    public function getBulkHireEligibleCountProperty(): int
    {
        if ($this->bulkSelectedIds === []) {
            return 0;
        }

        return VirtualUser::query()
            ->whereIn('id', $this->bulkSelectedIds)
            ->where('status', VirtualUserStatus::DEACTIVATED)
            ->count();
    }

    public function getCurrentPageAllSelectedProperty(): bool
    {
        $currentPageIds = collect($this->users->items())
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($currentPageIds === []) {
            return false;
        }

        return count(array_intersect($currentPageIds, $this->bulkSelectedIds)) === count($currentPageIds);
    }

    protected function applyBulkOperationResult(BulkOperationResult $result, string $successLabel): void
    {
        $this->bulkResultSuccessIds = $result->successVirtualUserIds;
        $this->bulkResultSkippedIds = $result->skippedVirtualUserIds;
        $this->bulkResultFailures = $result->failures;

        $this->setFlashMessage(
            __('permission-registry::messages.bulk_operation_summary', [
                'label' => $successLabel,
                'success' => $result->successCount(),
                'skipped' => $result->skippedCount(),
                'failed' => $result->failedCount(),
            ])
        );

        $this->showBulkResultModal = $result->hasFailures();
    }
}
