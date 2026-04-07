<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Jobs\GrantMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Jobs\RevokeMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Models\VirtualUserGroup;
use ArcheeNic\PermissionRegistry\Models\VirtualUserPosition;

class ReconcileUserPermissionsAction
{
    public function handle(int $userId): void
    {
        $targetPermissionIds = $this->getTargetPermissionIds($userId);

        $currentEnabledPermissionIds = GrantedPermission::query()
            ->where('virtual_user_id', $userId)
            ->where('enabled', true)
            ->pluck('permission_id')
            ->toArray();

        $permissionsToGrant = array_values(array_diff($targetPermissionIds, $currentEnabledPermissionIds));

        $autoGrantedEnabledPermissionIds = GrantedPermission::query()
            ->where('virtual_user_id', $userId)
            ->where('enabled', true)
            ->where('meta->auto_granted', true)
            ->pluck('permission_id')
            ->toArray();

        $permissionsToRevoke = array_values(array_diff($autoGrantedEnabledPermissionIds, $targetPermissionIds));

        if (!empty($permissionsToGrant)) {
            $permissionsData = array_map(static fn (int $permissionId): array => [
                'permissionId' => $permissionId,
                'fieldValues' => [],
                'meta' => [
                    'auto_granted' => true,
                    'auto_grant_source' => 'reconcile',
                ],
                'expiresAt' => null,
            ], $permissionsToGrant);

            GrantMultiplePermissionsJob::dispatch($userId, $permissionsData);
        }

        if (!empty($permissionsToRevoke)) {
            RevokeMultiplePermissionsJob::dispatch($userId, $permissionsToRevoke);
        }
    }

    /**
     * @return array<int>
     */
    private function getTargetPermissionIds(int $userId): array
    {
        $permissionIds = [];

        $positionIds = VirtualUserPosition::query()
            ->where('virtual_user_id', $userId)
            ->pluck('position_id')
            ->toArray();

        foreach ($positionIds as $positionId) {
            $this->collectPositionPermissionIds($positionId, $permissionIds);
        }

        $groupIds = VirtualUserGroup::query()
            ->where('virtual_user_id', $userId)
            ->pluck('permission_group_id')
            ->toArray();

        foreach ($groupIds as $groupId) {
            $this->collectGroupPermissionIds($groupId, $permissionIds);
        }

        return array_values(array_unique($permissionIds));
    }

    /**
     * @param array<int> $permissionIds
     * @param array<int> $processedPositions
     */
    private function collectPositionPermissionIds(
        int $positionId,
        array &$permissionIds,
        array $processedPositions = []
    ): void {
        if (in_array($positionId, $processedPositions, true)) {
            return;
        }

        $processedPositions[] = $positionId;

        $position = Position::query()
            ->with([
                'permissions' => fn ($query) => $query->where('auto_grant', true),
                'groups.permissions' => fn ($query) => $query->where('auto_grant', true),
                'parent',
            ])
            ->find($positionId);

        if (!$position) {
            return;
        }

        foreach ($position->permissions as $permission) {
            $permissionIds[] = $permission->id;
        }

        foreach ($position->groups as $group) {
            foreach ($group->permissions as $permission) {
                $permissionIds[] = $permission->id;
            }
        }

        if ($position->parent) {
            $this->collectPositionPermissionIds($position->parent->id, $permissionIds, $processedPositions);
        }
    }

    /**
     * @param array<int> $permissionIds
     */
    private function collectGroupPermissionIds(int $groupId, array &$permissionIds): void
    {
        $group = PermissionGroup::query()
            ->with(['permissions' => fn ($query) => $query->where('auto_grant', true)])
            ->find($groupId);

        if (!$group) {
            return;
        }

        foreach ($group->permissions as $permission) {
            $permissionIds[] = $permission->id;
        }
    }
}
