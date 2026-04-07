<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\DataTransferObjects\BulkOperationResult;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use Illuminate\Database\QueryException;

class BulkAssignVirtualUserPositionAction
{
    private const USER_NOT_FOUND_MESSAGE = 'Virtual user not found';
    private const ASSIGN_FAILED_MESSAGE = 'Failed to assign position';

    public function __construct(
        private AssignVirtualUserPositionAction $assignVirtualUserPositionAction
    ) {
    }

    /**
     * @param array<int> $virtualUserIds
     */
    public function handle(array $virtualUserIds, int $positionId): BulkOperationResult
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
            if (!$user) {
                $result->addFailure($virtualUserId, self::USER_NOT_FOUND_MESSAGE);
                continue;
            }

            try {
                $this->assignVirtualUserPositionAction->handle($virtualUserId, $positionId);
                $result->addSuccess($virtualUserId);
            } catch (QueryException $e) {
                if ($this->isDuplicateKeyException($e)) {
                    $result->addSkipped($virtualUserId);
                    continue;
                }

                report($e);
                $result->addFailure($virtualUserId, self::ASSIGN_FAILED_MESSAGE);
            } catch (\Throwable $e) {
                report($e);
                $result->addFailure($virtualUserId, self::ASSIGN_FAILED_MESSAGE);
            }
        }

        return $result;
    }

    private function isDuplicateKeyException(QueryException $e): bool
    {
        $message = mb_strtolower($e->getMessage());
        $code = (string) $e->getCode();

        return str_contains($message, 'unique')
            || str_contains($message, 'duplicate')
            || in_array($code, ['23000', '23505'], true);
    }
}
