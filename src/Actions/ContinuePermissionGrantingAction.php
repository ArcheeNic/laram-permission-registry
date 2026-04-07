<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use Illuminate\Support\Facades\Log;

class ContinuePermissionGrantingAction
{
    public function __construct(
        private RetryTriggerFromFailedAction $retryAction
    ) {
    }

    /**
     * Продолжить выдачу права с последнего упавшего триггера
     *
     * @param int $grantedPermissionId
     * @return bool
     */
    public function execute(int $grantedPermissionId): bool
    {
        $grantedPermission = GrantedPermission::with('executionLogs')->find($grantedPermissionId);

        if (!$grantedPermission) {
            Log::error('GrantedPermission not found for continue granting', [
                'granted_permission_id' => $grantedPermissionId,
            ]);
            return false;
        }

        // Получить ID упавшего триггера из meta или из логов
        $failedTriggerId = $this->getFailedTriggerId($grantedPermission);

        if (!$failedTriggerId) {
            Log::warning('No failed trigger found to continue', [
                'granted_permission_id' => $grantedPermissionId,
            ]);
            return false;
        }

        // Делегировать в RetryTriggerFromFailedAction
        return $this->retryAction->execute($grantedPermissionId, $failedTriggerId);
    }

    /**
     * Получить ID упавшего триггера из meta или из логов
     */
    private function getFailedTriggerId(GrantedPermission $grantedPermission): ?int
    {
        // Сначала проверяем meta
        $meta = $grantedPermission->meta ?? [];
        if (!empty($meta['last_failed_trigger_id'])) {
            return (int) $meta['last_failed_trigger_id'];
        }

        // Fallback: ищем в логах последний упавший триггер
        $lastFailedLog = $grantedPermission->executionLogs()
            ->where('status', 'failed')
            ->orderByDesc('created_at')
            ->first();

        return $lastFailedLog?->permission_trigger_id;
    }
}
