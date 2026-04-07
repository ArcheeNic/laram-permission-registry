<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Services\HrEventTriggerExecutor;
use Illuminate\Support\Facades\DB;

class HireVirtualUserAction
{
    public function __construct(
        private ReconcileUserPermissionsAction $reconcileUserPermissionsAction,
        private HrEventTriggerExecutor $hrEventTriggerExecutor
    ) {
    }

    /**
     * @param array<int> $positionIds
     * @param array<int> $groupIds
     */
    public function handle(
        int $userId,
        array $positionIds = [],
        array $groupIds = [],
        EmployeeCategory|string $employeeCategory = EmployeeCategory::STAFF
    ): VirtualUser
    {
        $resolvedCategory = $this->resolveCategory($employeeCategory);

        $user = DB::transaction(function () use ($userId, $positionIds, $groupIds, $resolvedCategory): VirtualUser {
            $user = VirtualUser::query()->findOrFail($userId);

            if (!empty($positionIds)) {
                $user->positions()->syncWithoutDetaching($positionIds);
            }

            if (!empty($groupIds)) {
                $user->groups()->syncWithoutDetaching($groupIds);
            }

            $user->status = VirtualUserStatus::ACTIVE;
            $user->employee_category = $resolvedCategory;
            $user->save();

            $this->reconcileUserPermissionsAction->handle($user->id);

            return $user->fresh();
        });

        $this->hrEventTriggerExecutor->execute($user->id, 'hire');

        return $user;
    }

    private function resolveCategory(EmployeeCategory|string $employeeCategory): EmployeeCategory
    {
        if ($employeeCategory instanceof EmployeeCategory) {
            return $employeeCategory;
        }

        $resolved = EmployeeCategory::tryFrom($employeeCategory);
        if ($resolved) {
            return $resolved;
        }

        throw new \InvalidArgumentException("Unknown employee category: {$employeeCategory}");
    }
}
