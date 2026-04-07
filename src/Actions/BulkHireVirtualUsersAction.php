<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\DataTransferObjects\BulkOperationResult;
use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;

class BulkHireVirtualUsersAction
{
    private const USER_NOT_FOUND_MESSAGE = 'Virtual user not found';
    private const HIRE_FAILED_MESSAGE = 'Failed to hire user';

    public function __construct(
        private HireVirtualUserAction $hireVirtualUserAction
    ) {
    }

    /**
     * @param array<int> $virtualUserIds
     * @param array<int> $positionIds
     * @param array<int> $groupIds
     */
    public function handle(
        array $virtualUserIds,
        array $positionIds = [],
        array $groupIds = [],
        EmployeeCategory|string $employeeCategory = EmployeeCategory::STAFF
    ): BulkOperationResult {
        $result = new BulkOperationResult;
        if ($virtualUserIds === []) {
            return $result;
        }

        $usersById = VirtualUser::query()
            ->whereIn('id', $virtualUserIds)
            ->get()
            ->keyBy('id');

        foreach ($virtualUserIds as $virtualUserId) {
            $user = $usersById->get($virtualUserId);
            if (!$user) {
                $result->addFailure($virtualUserId, self::USER_NOT_FOUND_MESSAGE);
                continue;
            }

            if ($user->status !== VirtualUserStatus::DEACTIVATED) {
                $result->addSkipped($virtualUserId);
                continue;
            }

            try {
                $this->hireVirtualUserAction->handle(
                    $virtualUserId,
                    $positionIds,
                    $groupIds,
                    $employeeCategory
                );
                $result->addSuccess($virtualUserId);
            } catch (\Throwable $e) {
                report($e);
                $result->addFailure($virtualUserId, self::HIRE_FAILED_MESSAGE);
            }
        }

        return $result;
    }
}
