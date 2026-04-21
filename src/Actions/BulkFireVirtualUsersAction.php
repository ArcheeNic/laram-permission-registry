<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\DataTransferObjects\BulkOperationResult;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;

class BulkFireVirtualUsersAction
{
    private const USER_NOT_FOUND_MESSAGE = 'Virtual user not found';

    private const FIRE_FAILED_MESSAGE = 'Failed to fire user';

    public function __construct(
        private FireVirtualUserAction $fireVirtualUserAction
    ) {}

    /**
     * @param  array<int>  $virtualUserIds
     */
    public function handle(array $virtualUserIds): BulkOperationResult
    {
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
            if (! $user) {
                $result->addFailure($virtualUserId, self::USER_NOT_FOUND_MESSAGE);

                continue;
            }

            if ($user->status === VirtualUserStatus::DEACTIVATED) {
                $result->addSkipped($virtualUserId);

                continue;
            }

            try {
                $this->fireVirtualUserAction->handle($virtualUserId);
                $result->addSuccess($virtualUserId);
            } catch (\Throwable $e) {
                report($e);
                $result->addFailure($virtualUserId, self::FIRE_FAILED_MESSAGE);
            }
        }

        return $result;
    }
}
