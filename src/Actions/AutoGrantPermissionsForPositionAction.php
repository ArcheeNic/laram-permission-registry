<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\PermissionScope;
use ArcheeNic\PermissionRegistry\Jobs\GrantMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\Position;
use Illuminate\Support\Facades\Log;

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

        $uniqueIds = array_values(array_unique($permissionIds));

        if (empty($uniqueIds)) {
            return;
        }

        $permissions = Permission::whereIn('id', $uniqueIds)->get();

        $permissionsData = [];

        foreach ($permissions as $permission) {
            if (($permission->scope ?? PermissionScope::Service) === PermissionScope::Resource) {
                Log::warning('Skipping resource-scoped permission in auto-grant via position', [
                    'permission_id' => $permission->id,
                    'position_id' => $positionId,
                    'virtual_user_id' => $userId,
                ]);
                continue;
            }

            $exists = GrantedPermission::where('virtual_user_id', $userId)
                ->where('permission_id', $permission->id)
                ->whereNull('resource_id')
                ->exists();

            if (!$exists) {
                $permissionsData[] = [
                    'permissionId' => $permission->id,
                    'resourceId' => null,
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
