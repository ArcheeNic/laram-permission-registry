<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\PermissionScope;
use ArcheeNic\PermissionRegistry\Jobs\GrantMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;

class AutoGrantPermissionsForGroupAction
{
    /**
     * Автоматическая выдача прав с флагом auto_grant при назначении группы.
     * Для resource-scoped прав ресурсы берутся из permission_group_resources.
     */
    public function handle(int $userId, int $groupId): void
    {
        $group = PermissionGroup::query()
            ->with([
                'permissions' => fn ($q) => $q->where('auto_grant', true),
                'permissionResources',
            ])
            ->find($groupId);

        if (! $group) {
            return;
        }

        $resourcesByPermission = [];
        foreach ($group->permissionResources as $row) {
            $resourcesByPermission[$row->permission_id][] = $row->resource_id;
        }

        $permissionsData = [];

        foreach ($group->permissions as $permission) {
            $scope = $permission->scope ?? PermissionScope::Service;

            if ($scope === PermissionScope::Resource) {
                foreach ($resourcesByPermission[$permission->id] ?? [] as $resourceId) {
                    if ($this->grantExists($userId, $permission->id, $resourceId)) {
                        continue;
                    }
                    $permissionsData[] = [
                        'permissionId' => $permission->id,
                        'resourceId' => $resourceId,
                        'fieldValues' => [],
                        'meta' => ['auto_granted' => true, 'auto_grant_source' => 'group'],
                        'expiresAt' => null,
                    ];
                }

                continue;
            }

            if ($this->grantExists($userId, $permission->id, null)) {
                continue;
            }

            $permissionsData[] = [
                'permissionId' => $permission->id,
                'resourceId' => null,
                'fieldValues' => [],
                'meta' => ['auto_granted' => true, 'auto_grant_source' => 'group'],
                'expiresAt' => null,
            ];
        }

        if (! empty($permissionsData)) {
            GrantMultiplePermissionsJob::dispatch($userId, $permissionsData);
        }
    }

    private function grantExists(int $userId, int $permissionId, ?int $resourceId): bool
    {
        $query = GrantedPermission::query()
            ->where('virtual_user_id', $userId)
            ->where('permission_id', $permissionId);

        if ($resourceId === null) {
            $query->whereNull('resource_id');
        } else {
            $query->where('resource_id', $resourceId);
        }

        return $query->exists();
    }
}
