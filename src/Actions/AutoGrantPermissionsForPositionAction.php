<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Jobs\GrantMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Position;

class AutoGrantPermissionsForPositionAction
{
    /**
     * Автоматическая выдача прав с флагом auto_grant при назначении должности.
     * Делегирует в GrantMultiplePermissionsJob — джоба сортирует по зависимостям
     * и выполняет триггеры последовательно.
     */
    public function handle(int $userId, int $positionId): void
    {
        $permissionIds = [];
        $this->collectPositionPermissionsForAutoGrant($positionId, $permissionIds);

        $uniqueIds = array_unique($permissionIds);

        if (empty($uniqueIds)) {
            return;
        }

        $permissionsData = [];

        foreach ($uniqueIds as $permissionId) {
            $exists = GrantedPermission::where('virtual_user_id', $userId)
                ->where('permission_id', $permissionId)
                ->exists();

            if (!$exists) {
                $permissionsData[] = [
                    'permissionId' => $permissionId,
                    'fieldValues' => [],
                    'meta' => ['auto_granted' => true, 'auto_grant_source' => 'position'],
                    'expiresAt' => null,
                ];
            }
        }

        if (!empty($permissionsData)) {
            GrantMultiplePermissionsJob::dispatch($userId, $permissionsData);
        }
    }

    private function collectPositionPermissionsForAutoGrant(
        int $positionId,
        array &$permissionIds,
        array $processedPositions = []
    ): void {
        if (in_array($positionId, $processedPositions)) {
            return;
        }

        $processedPositions[] = $positionId;

        $position = Position::with(['permissions' => function ($query) {
            $query->where('auto_grant', true);
        }, 'groups.permissions' => function ($query) {
            $query->where('auto_grant', true);
        }, 'parent'])->find($positionId);

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
            $this->collectPositionPermissionsForAutoGrant($position->parent->id, $permissionIds, $processedPositions);
        }
    }
}
