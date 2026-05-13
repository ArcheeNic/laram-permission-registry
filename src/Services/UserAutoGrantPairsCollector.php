<?php

namespace ArcheeNic\PermissionRegistry\Services;

use ArcheeNic\PermissionRegistry\Enums\PermissionScope;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;

class UserAutoGrantPairsCollector
{
    /**
     * Возвращает множество пар (permission_id, resource_id) с auto_grant=true,
     * которые получает пользователь через свои текущие группы и должности,
     * за исключением указанной группы/должности.
     *
     * @return array<string, true>  ключ: "permission_id|resource_id" (resource_id="" если null)
     */
    public function collect(int $userId, ?int $excludeGroupId = null, ?int $excludePositionId = null): array
    {
        $pairs = [];

        $user = VirtualUser::query()
            ->with([
                'groups',
                'positions',
            ])
            ->find($userId);

        if (!$user) {
            return $pairs;
        }

        foreach ($user->groups as $group) {
            if ($excludeGroupId !== null && $group->id === $excludeGroupId) {
                continue;
            }
            $this->collectFromGroup($group->id, $pairs);
        }

        foreach ($user->positions as $position) {
            $this->collectFromPosition($position->id, $pairs, $excludeGroupId, $excludePositionId);
        }

        return $pairs;
    }

    /**
     * @param array<string, true> $pairs
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
            $this->addPermissionPairs($permission, $resourcesByPermission, $pairs);
        }
    }

    /**
     * @param array<string, true> $pairs
     */
    private function collectFromPosition(
        int $positionId,
        array &$pairs,
        ?int $excludeGroupId,
        ?int $excludePositionId,
        array $processed = []
    ): void {
        if (in_array($positionId, $processed, true)) {
            return;
        }
        $processed[] = $positionId;

        if ($excludePositionId !== null && $positionId === $excludePositionId) {
            if (($position = Position::query()->with('parent')->find($positionId)) && $position->parent) {
                $this->collectFromPosition($position->parent->id, $pairs, $excludeGroupId, $excludePositionId, $processed);
            }
            return;
        }

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
            $this->addPermissionPairs($permission, $resourcesByPermission, $pairs);
        }

        foreach ($position->groups as $group) {
            if ($excludeGroupId !== null && $group->id === $excludeGroupId) {
                continue;
            }
            $this->collectFromGroup($group->id, $pairs);
        }

        if ($position->parent) {
            $this->collectFromPosition($position->parent->id, $pairs, $excludeGroupId, $excludePositionId, $processed);
        }
    }

    /**
     * @param array<int, array<int>> $resourcesByPermission
     * @param array<string, true>    $pairs
     */
    private function addPermissionPairs(
        $permission,
        array $resourcesByPermission,
        array &$pairs
    ): void {
        $scope = $permission->scope ?? PermissionScope::Service;

        if ($scope === PermissionScope::Resource) {
            foreach ($resourcesByPermission[$permission->id] ?? [] as $resourceId) {
                $pairs[$permission->id.'|'.$resourceId] = true;
            }
            return;
        }

        $pairs[$permission->id.'|'] = true;
    }

    public static function key(int $permissionId, ?int $resourceId): string
    {
        return $permissionId.'|'.($resourceId ?? '');
    }
}
