<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\PermissionScope;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use ArcheeNic\PermissionRegistry\Services\UserAutoGrantPairsCollector;
use Illuminate\Support\Facades\Log;

class AutoRevokePermissionsForGroupAction
{
    public function __construct(
        private RevokePermissionAction $revokePermissionAction,
        private UserAutoGrantPairsCollector $pairsCollector,
    ) {}

    /**
     * Автоотзыв прав с флагом auto_revoke при отзыве группы.
     * Для resource-scoped прав ресурсы берутся из permission_group_resources.
     * Перед отзывом проверяется, остаются ли у пользователя другие источники
     * этой пары (permission_id, resource_id) — если да, отзыв пропускается.
     */
    public function handle(int $userId, int $groupId): void
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

        $remainingPairs = $this->pairsCollector->collect($userId, excludeGroupId: $groupId);

        foreach ($group->permissions as $permission) {
            $scope = $permission->scope ?? PermissionScope::Service;

            if ($scope === PermissionScope::Resource) {
                foreach ($resourcesByPermission[$permission->id] ?? [] as $resourceId) {
                    $this->revokePair($userId, $permission->id, $resourceId, $remainingPairs);
                }

                continue;
            }

            $this->revokePair($userId, $permission->id, null, $remainingPairs);
        }
    }

    /**
     * @param  array<string, true>  $remainingPairs
     */
    private function revokePair(int $userId, int $permissionId, ?int $resourceId, array $remainingPairs): void
    {
        $key = UserAutoGrantPairsCollector::key($permissionId, $resourceId);
        if (isset($remainingPairs[$key])) {
            return;
        }

        $exists = GrantedPermission::query()
            ->where('virtual_user_id', $userId)
            ->where('permission_id', $permissionId)
            ->when($resourceId === null, fn ($q) => $q->whereNull('resource_id'))
            ->when($resourceId !== null, fn ($q) => $q->where('resource_id', $resourceId))
            ->where('enabled', true)
            ->exists();

        if (! $exists) {
            return;
        }

        try {
            $this->revokePermissionAction->handle(
                userId: $userId,
                permissionId: $permissionId,
                skipTriggers: false,
                resourceId: $resourceId,
            );
        } catch (\Exception $e) {
            Log::warning(sprintf(
                'Failed to auto-revoke permission %d (resource=%s) from user %d: %s',
                $permissionId,
                $resourceId ?? 'null',
                $userId,
                $e->getMessage(),
            ));
        }
    }
}
