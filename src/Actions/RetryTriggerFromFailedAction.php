<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Services\PermissionTriggerExecutor;
use Illuminate\Support\Facades\Log;

class RetryTriggerFromFailedAction
{
    public function __construct(
        private PermissionTriggerExecutor $executor
    ) {
    }

    /**
     * Продолжить выполнение триггеров с упавшего шага (ручное продолжение с введёнными полями)
     *
     * @param int $grantedPermissionId
     * @param int $failedTriggerId
     * @param array $manualFieldValues значения полей по имени триггера (напр. first_name, password)
     * @return bool
     */
    public function execute(int $grantedPermissionId, int $failedTriggerId, array $manualFieldValues = []): bool
    {
        // Получить granted_permission с правами
        $grantedPermission = GrantedPermission::with('permission.triggerAssignments.trigger')
            ->find($grantedPermissionId);

        if (!$grantedPermission) {
            Log::error('GrantedPermission not found for retry', [
                'granted_permission_id' => $grantedPermissionId,
            ]);
            return false;
        }

        // Определить тип события из последнего execution log
        // (статус 'failed' может быть как у grant, так и у revoke)
        $lastLog = $grantedPermission->executionLogs()
            ->orderByDesc('created_at')
            ->first();

        $eventType = $lastLog?->event_type ?? 'grant';

        // Получить все триггеры для этого права
        $assignments = $grantedPermission->permission
            ->triggerAssignments()
            ->where('event_type', $eventType)
            ->where('is_enabled', true)
            ->with('trigger')
            ->orderBy('order')
            ->get();

        if ($assignments->isEmpty()) {
            Log::warning('No triggers found for retry', [
                'granted_permission_id' => $grantedPermissionId,
                'event_type' => $eventType,
            ]);
            return false;
        }

        // Найти позицию упавшего триггера
        $startIndex = $assignments->search(fn($a) => $a->permission_trigger_id === $failedTriggerId);

        if ($startIndex === false) {
            Log::error('Failed trigger not found in assignments', [
                'granted_permission_id' => $grantedPermissionId,
                'failed_trigger_id' => $failedTriggerId,
            ]);
            return false;
        }

        // Запустить цепочку с упавшего триггера с ручными значениями полей
        return $this->executor->executeChainFromTrigger(
            $grantedPermission,
            $assignments,
            $startIndex,
            $eventType,
            $manualFieldValues
        );
    }
}
