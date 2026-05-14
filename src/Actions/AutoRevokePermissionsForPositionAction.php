<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\PermissionScope;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Services\UserAutoGrantPairsCollector;
use Illuminate\Support\Facades\Log;

class AutoRevokePermissionsForPositionAction
{
    public function __construct(
        private RevokePermissionAction $revokePermissionAction,
        private UserAutoGrantPairsCollector $pairsCollector,
    ) {}

    /**
     * Автоотзыв прав с флагом auto_revoke при отзыве должности.
     * Учитывает прямые права должности, права её групп и parent-должности.
     * Для resource-scoped прав ресурсы берутся из position_permission_resources
     * (для прямых прав) и permission_group_resources (для прав групп).
     */
    public function handle(int $userId, int $positionId): void
    {
        $pairs = [];
        $this->collect($positionId, $pairs);

        $unique = [];
        foreach ($pairs as $pair) {
            $key = UserAutoGrantPairsCollector::key($pair['permission_id'], $pair['resource_id']);
            $unique[$key] = $pair;
        }

        $remainingPairs = $this->pairsCollector->collect($userId, excludePositionId: $positionId);

        foreach ($unique as $key => $pair) {
            if (isset($remainingPairs[$key])) {
                continue;
            }

            $exists = GrantedPermission::query()
                ->where('virtual_user_id', $userId)
                ->where('permission_id', $pair['permission_id'])
                ->when($pair['resource_id'] === null, fn ($q) => $q->whereNull('resource_id'))
                ->when($pair['resource_id'] !== null, fn ($q) => $q->where('resource_id', $pair['resource_id']))
                ->where('enabled', true)
                ->exists();

            if (! $exists) {
                continue;
            }

            try {
                $this->revokePermissionAction->handle(
                    userId: $userId,
                    permissionId: $pair['permission_id'],
                    skipTriggers: false,
                    resourceId: $pair['resource_id'],
                );
            } catch (\Exception $e) {
                Log::warning(sprintf(
                    'Failed to auto-revoke permission %d (resource=%s) from user %d: %s',
                    $pair['permission_id'],
                    $pair['resource_id'] ?? 'null',
                    $userId,
                    $e->getMessage(),
                ));
            }
        }
    }

    /**
     * @param  array<int, array{permission_id:int, resource_id:?int}>  $pairs
     */
    private function collect(int $positionId, array &$pairs, array $processed = []): void
    {
        if (in_array($positionId, $processed, true)) {
            return;
        }
        $processed[] = $positionId;

        $position = Position::query()
            ->with([
                'permissions' => fn ($q) => $q->where('auto_revoke', true),
                'permissionResources',
                'groups',
                'parent',
            ])
            ->find($positionId);

        if (! $position) {
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
     * @param  array<int, array{permission_id:int, resource_id:?int}>  $pairs
     */
    private function collectFromGroup(int $groupId, array &$pairs): void
    {
        $group = PermissionGroup::query()
            ->with([
                'permissions' => fn ($q) => $q->where('auto_revoke', true),
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

        foreach ($group->permissions as $permission) {
            $this->appendPermissionPairs($permission, $resourcesByPermission, $pairs);
        }
    }

    /**
     * @param  array<int, array<int>>  $resourcesByPermission
     * @param  array<int, array{permission_id:int, resource_id:?int}>  $pairs
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
}
