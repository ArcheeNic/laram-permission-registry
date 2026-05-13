<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\PermissionScope;
use ArcheeNic\PermissionRegistry\Jobs\GrantMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use ArcheeNic\PermissionRegistry\Models\Position;

class AutoGrantPermissionsForPositionAction
{
    /**
     * Автовыдача прав с флагом auto_grant при назначении должности.
     * Учитывает прямые права должности, права её групп и parent-должности.
     * Для resource-scoped прав ресурсы берутся из position_permission_resources
     * (для прямых прав должности) и permission_group_resources (для прав групп).
     */
    public function handle(int $userId, int $positionId): void
    {
        $pairs = [];
        $this->collect($positionId, $pairs);

        $unique = [];
        foreach ($pairs as $pair) {
            $key = $pair['permission_id'].'|'.($pair['resource_id'] ?? '');
            $unique[$key] = $pair;
        }

        $permissionsData = [];

        foreach ($unique as $pair) {
            if ($this->grantExists($userId, $pair['permission_id'], $pair['resource_id'])) {
                continue;
            }

            $permissionsData[] = [
                'permissionId' => $pair['permission_id'],
                'resourceId' => $pair['resource_id'],
                'fieldValues' => [],
                'meta' => ['auto_granted' => true, 'auto_grant_source' => 'position'],
                'expiresAt' => null,
            ];
        }

        if (!empty($permissionsData)) {
            GrantMultiplePermissionsJob::dispatch($userId, $permissionsData);
        }
    }

    /**
     * @param array<int, array{permission_id:int, resource_id:?int}> $pairs
     */
    private function collect(int $positionId, array &$pairs, array $processed = []): void
    {
        if (in_array($positionId, $processed, true)) {
            return;
        }
        $processed[] = $positionId;

        $position = Position::query()
            ->with([
                'permissions' => fn ($q) => $q->where('auto_grant', true),
                'permissionResources',
                'groups',
                'parent',
            ])
            ->find($positionId);

        if (!$position) {
            return;
        }

        $resourcesByPermission = [];
        foreach ($position->permissionResources as $row) {
            $resourcesByPermission[$row->permission_id][] = $row->resource_id;
        }

        foreach ($position->permissions as $permission) {
            $this->appendPermissionPairs($permission, $resourcesByPermission, $pairs);
        }

        foreach ($position->groups as $group) {
            $this->collectFromGroup($group->id, $pairs);
        }

        if ($position->parent) {
            $this->collect($position->parent->id, $pairs, $processed);
        }
    }

    /**
     * @param array<int, array{permission_id:int, resource_id:?int}> $pairs
     */
    private function collectFromGroup(int $groupId, array &$pairs): void
    {
        $group = PermissionGroup::query()
            ->with([
                'permissions' => fn ($q) => $q->where('auto_grant', true),
                'permissionResources',
            ])
            ->find($groupId);

        if (!$group) {
            return;
        }

        $resourcesByPermission = [];
        foreach ($group->permissionResources as $row) {
            $resourcesByPermission[$row->permission_id][] = $row->resource_id;
        }

        foreach ($group->permissions as $permission) {
            $this->appendPermissionPairs($permission, $resourcesByPermission, $pairs);
        }
    }

    /**
     * @param array<int, array<int>> $resourcesByPermission
     * @param array<int, array{permission_id:int, resource_id:?int}> $pairs
     */
    private function appendPermissionPairs($permission, array $resourcesByPermission, array &$pairs): void
    {
        $scope = $permission->scope ?? PermissionScope::Service;

        if ($scope === PermissionScope::Resource) {
            foreach ($resourcesByPermission[$permission->id] ?? [] as $resourceId) {
                $pairs[] = ['permission_id' => $permission->id, 'resource_id' => $resourceId];
            }
            return;
        }

        $pairs[] = ['permission_id' => $permission->id, 'resource_id' => null];
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
