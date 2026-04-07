<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\ManagementMode;
use ArcheeNic\PermissionRegistry\Enums\ManualTaskStatus;
use ArcheeNic\PermissionRegistry\Jobs\RevokeMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\ManualProvisionTask;

class ProcessFireRevocationsAction
{
    public function __construct(
        private CreateManualProvisionTaskAction $createManualProvisionTaskAction
    ) {
    }

    /**
     * @return array{automated_revokes_dispatched:int, manual_tasks_created:int, remaining_permissions_count:int}
     */
    public function handle(
        int $userId,
        bool $dispatchAutomatedRevokes = true,
        bool $createManualTasks = true,
        bool $includeAutoGranted = false
    ): array
    {
        $remainingPermissionsQuery = GrantedPermission::query()
            ->where('virtual_user_id', $userId)
            ->where('enabled', true)
            ->when(! $includeAutoGranted, function ($query) {
                $query->where(function ($nestedQuery) {
                    $nestedQuery->whereNull('meta->auto_granted')
                        ->orWhere('meta->auto_granted', false);
                });
            })
            ->with('permission');

        $remainingPermissions = $remainingPermissionsQuery->get();

        $permissionIdsToRevoke = [];
        $manualTasksCreated = 0;

        foreach ($remainingPermissions as $grantedPermission) {
            if (! $grantedPermission->permission) {
                continue;
            }

            $managementMode = $this->resolveManagementMode($grantedPermission->permission->management_mode);

            if ($managementMode === ManagementMode::AUTOMATED) {
                $permissionIdsToRevoke[] = $grantedPermission->permission_id;
                continue;
            }

            if (! $createManualTasks || $this->hasOpenRevocationTask($grantedPermission->id)) {
                continue;
            }

            $task = $this->createManualProvisionTaskAction->handle(
                grantedPermission: $grantedPermission,
                permission: $grantedPermission->permission
            );

            $task->update([
                ManualProvisionTask::TITLE => __('permission-registry::governance.manual_revoke_task_title', [
                    'permission' => $grantedPermission->permission->name,
                    'service' => $grantedPermission->permission->service,
                ]),
                ManualProvisionTask::DESCRIPTION => __('permission-registry::governance.manual_revoke_task_description'),
            ]);

            $manualTasksCreated++;
        }

        $uniquePermissionIdsToRevoke = array_values(array_unique($permissionIdsToRevoke));
        if ($dispatchAutomatedRevokes && ! empty($uniquePermissionIdsToRevoke)) {
            RevokeMultiplePermissionsJob::dispatch($userId, $uniquePermissionIdsToRevoke)->afterCommit();
        }

        return [
            'automated_revokes_dispatched' => count($uniquePermissionIdsToRevoke),
            'manual_tasks_created' => $manualTasksCreated,
            'remaining_permissions_count' => $remainingPermissions->count(),
        ];
    }

    private function resolveManagementMode(mixed $rawMode): ManagementMode
    {
        if ($rawMode instanceof ManagementMode) {
            return $rawMode;
        }

        if (is_string($rawMode)) {
            return ManagementMode::tryFrom($rawMode) ?? ManagementMode::AUTOMATED;
        }

        return ManagementMode::AUTOMATED;
    }

    private function hasOpenRevocationTask(int $grantedPermissionId): bool
    {
        return ManualProvisionTask::query()
            ->where(ManualProvisionTask::GRANTED_PERMISSION_ID, $grantedPermissionId)
            ->whereIn(ManualProvisionTask::STATUS, [
                ManualTaskStatus::PENDING->value,
                ManualTaskStatus::IN_PROGRESS->value,
            ])
            ->exists();
    }
}

