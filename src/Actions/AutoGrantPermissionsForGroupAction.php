<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Jobs\GrantMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;

class AutoGrantPermissionsForGroupAction
{
    /**
     * Автоматическая выдача прав с флагом auto_grant при назначении группы.
     * Делегирует в GrantMultiplePermissionsJob — джоба сортирует по зависимостям
     * и выполняет триггеры последовательно.
     */
    public function handle(int $userId, int $groupId): void
    {
        $group = PermissionGroup::with(['permissions' => function ($query) {
            $query->where('auto_grant', true);
        }])->find($groupId);

        if (!$group) {
            return;
        }

        $permissionsData = [];

        foreach ($group->permissions as $permission) {
            $exists = GrantedPermission::where('virtual_user_id', $userId)
                ->where('permission_id', $permission->id)
                ->exists();

            if (!$exists) {
                $permissionsData[] = [
                    'permissionId' => $permission->id,
                    'fieldValues' => [],
                    'meta' => ['auto_granted' => true, 'auto_grant_source' => 'group'],
                    'expiresAt' => null,
                ];
            }
        }

        if (!empty($permissionsData)) {
            GrantMultiplePermissionsJob::dispatch($userId, $permissionsData);
        }
    }
}
