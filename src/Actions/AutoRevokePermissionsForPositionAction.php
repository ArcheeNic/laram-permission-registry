<?php

namespace ArcheeNic\PermissionRegistry\Actions;

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

        if (empty($permissionIds)) {
            return;
        }

        foreach (array_unique($permissionIds) as $permissionId) {
            try {
                $this->revokePermissionAction->handle(
                    userId: $userId,
                    permissionId: $permissionId,
                    skipTriggers: false
                );
            } catch (\Exception $e) {
                // Логируем ошибку, но продолжаем отзывать остальные права
                Log::warning("Failed to auto-revoke permission {$permissionId} from user {$userId}: " . $e->getMessage());
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
