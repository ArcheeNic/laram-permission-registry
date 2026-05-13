<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\PermissionScope;
use ArcheeNic\PermissionRegistry\Jobs\GrantMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use Illuminate\Support\Facades\Log;

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
            if (($permission->scope ?? PermissionScope::Service) === PermissionScope::Resource) {
                Log::warning('Skipping resource-scoped permission in auto-grant via group', [
                    'permission_id' => $permission->id,
                    'group_id' => $groupId,
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
