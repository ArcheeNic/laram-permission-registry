<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use Illuminate\Support\Facades\Log;

class AutoRevokePermissionsForGroupAction
{
    public function __construct(
        private RevokePermissionAction $revokePermissionAction
    ) {
    }

    /**
     * Автоматический отзыв прав с флагом auto_revoke при отзыве группы
     *
     * @param int $userId
     * @param int $groupId
     * @return void
     */
    public function handle(int $userId, int $groupId): void
    {
        $group = PermissionGroup::with(['permissions' => function ($query) {
            $query->where('auto_revoke', true);
        }])->find($groupId);

        if (!$group) {
            return;
        }

        foreach ($group->permissions as $permission) {
            try {
                $this->revokePermissionAction->handle(
                    userId: $userId,
                    permissionId: $permission->id,
                    skipTriggers: false
                );
            } catch (\Exception $e) {
                // Логируем ошибку, но продолжаем отзывать остальные права
                Log::warning("Failed to auto-revoke permission {$permission->id} from user {$userId}: " . $e->getMessage());
            }
        }
    }
}
