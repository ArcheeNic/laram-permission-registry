<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Position;
use Illuminate\Support\Facades\Log;

class AutoRevokePermissionsForPositionAction
{
    public function __construct(
        private RevokePermissionAction $revokePermissionAction
    ) {
    }

    /**
     * Автоматический отзыв прав с флагом auto_revoke при отзыве должности
     *
     * @param int $userId
     * @param int $positionId
     * @return void
     */
    public function handle(int $userId, int $positionId): void
    {
        $permissionIds = [];
        $this->collectPositionPermissionsForAutoRevoke($positionId, $permissionIds);

        $uniqueIds = array_values(array_unique($permissionIds));
        if (empty($uniqueIds)) {
            return;
        }

        $grants = GrantedPermission::query()
            ->where('virtual_user_id', $userId)
            ->whereIn('permission_id', $uniqueIds)
            ->where('enabled', true)
            ->get(['permission_id', 'resource_id']);

        foreach ($grants as $grant) {
            try {
                $this->revokePermissionAction->handle(
                    userId: $userId,
                    permissionId: $grant->permission_id,
                    skipTriggers: false,
                    resourceId: $grant->resource_id,
                );
            } catch (\Exception $e) {
                Log::warning(sprintf(
                    'Failed to auto-revoke permission %d (resource=%s) from user %d: %s',
                    $grant->permission_id, $grant->resource_id ?? 'null', $userId, $e->getMessage()
                ));
            }
        }
    }

    /**
     * Рекурсивный сбор прав для автоотзыва из должности, её групп и родительских должностей
     *
     * @param int $positionId
     * @param array $permissionIds
     * @param array $processedPositions
     * @return void
     */
    private function collectPositionPermissionsForAutoRevoke(
        int $positionId,
        array &$permissionIds,
        array $processedPositions = []
    ): void {
        if (in_array($positionId, $processedPositions)) {
            return;
        }

        $processedPositions[] = $positionId;

        $position = Position::with(['permissions' => function ($query) {
            $query->where('auto_revoke', true);
        }, 'groups.permissions' => function ($query) {
            $query->where('auto_revoke', true);
        }, 'parent'])->find($positionId);

        if (!$position) {
            return;
        }

        // Добавляем прямые права из должности
        foreach ($position->permissions as $permission) {
            $permissionIds[] = $permission->id;
        }

        // Добавляем права из групп должности
        foreach ($position->groups as $group) {
            foreach ($group->permissions as $permission) {
                $permissionIds[] = $permission->id;
            }
        }

        // Рекурсивно обрабатываем родительскую должность
        if ($position->parent) {
            $this->collectPositionPermissionsForAutoRevoke($position->parent->id, $permissionIds, $processedPositions);
        }
    }
}
