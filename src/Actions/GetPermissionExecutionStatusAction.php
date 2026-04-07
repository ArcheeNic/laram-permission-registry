<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\DataTransferObjects\TriggerStatusDto;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\PermissionExecutionLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GetPermissionExecutionStatusAction
{
    /**
     * Получить статусы выполнения триггеров для выданных прав пользователя
     *
     * @param int $userId
     * @param array $permissionIds - массив ID прав для проверки
     * @return array ['permission_id' => ['status' => string, 'permission_name' => string, 'triggers' => [TriggerStatusDto]]]
     */
    public function execute(int $userId, array $permissionIds): array
    {
        if (empty($permissionIds)) {
            return [];
        }

        // Получаем выданные права с логами выполнения
        $grantedPermissions = GrantedPermission::with([
            'permission',
            'executionLogs' => function ($query) {
                $query->with('trigger')
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc'); // Для стабильной сортировки
            }
        ])
            ->where('virtual_user_id', $userId)
            ->whereIn('permission_id', $permissionIds)
            ->get();

        $result = [];

        foreach ($grantedPermissions as $grantedPermission) {
            $permissionId = $grantedPermission->permission_id;

            // Определяем тип триггеров на основе статуса права и последних логов
            $eventType = 'grant';
            if (in_array($grantedPermission->status, ['revoking', 'revoked'])) {
                $eventType = 'revoke';
            } elseif (in_array($grantedPermission->status, ['failed', 'partially_granted'])) {
                // Для прав с ошибками проверяем последний лог, чтобы понять какое действие было
                $latestLog = $grantedPermission->executionLogs->first();
                if ($latestLog && $latestLog->event_type) {
                    $eventType = $latestLog->event_type;
                }
            }

            // Получаем все триггеры для этого права (из assignment)
            $assignedTriggers = $grantedPermission->permission
                ->triggerAssignments()
                ->where('event_type', $eventType)
                ->where('is_enabled', true)
                ->with('trigger')
                ->orderBy('order')
                ->get();

            $triggerStatuses = [];
            $hasRunningTriggers = false;
            $hasFailedTriggers = false;

            if ($assignedTriggers->isEmpty()) {
                // Нет активных триггеров, но проверим логи на наличие ошибок
                // Фильтруем логи по event_type чтобы показывать только логи текущего действия
                $logsWithErrors = $grantedPermission->executionLogs
                    ->where('event_type', $eventType)
                    ->where('status', 'failed');
                
                if ($logsWithErrors->isEmpty()) {
                    // Нет триггеров и нет ошибок - право уже выдано успешно
                    continue;
                }
                
                // Есть логи с ошибками, показываем их
                foreach ($logsWithErrors as $log) {
                    $hasFailedTriggers = true;
                    $triggerStatuses[] = new TriggerStatusDto(
                        triggerId: $log->permission_trigger_id,
                        triggerName: $log->trigger->name ?? "Триггер #{$log->permission_trigger_id}",
                        status: 'failed',
                        errorMessage: $log->error_message,
                        startedAt: $log->started_at,
                        completedAt: $log->completed_at,
                        meta: $log->meta
                    );
                }
            } else {
                // Есть активные триггеры, обрабатываем их
                foreach ($assignedTriggers as $assignment) {
                    $trigger = $assignment->trigger;

                    // Ищем самый свежий лог для этого триггера
                    $latestLog = $grantedPermission->executionLogs
                        ->where('permission_trigger_id', $trigger->id)
                        ->first();

                    if ($latestLog) {
                        $status = $latestLog->status;
                        
                        if ($status === 'running') {
                            $hasRunningTriggers = true;
                        } elseif ($status === 'failed') {
                            $hasFailedTriggers = true;
                        }

                        $triggerStatuses[] = new TriggerStatusDto(
                            triggerId: $trigger->id,
                            triggerName: $trigger->name,
                            status: $status,
                            errorMessage: $latestLog->error_message,
                            startedAt: $latestLog->started_at,
                            completedAt: $latestLog->completed_at,
                            meta: $latestLog->meta
                        );
                    } else {
                        // Триггер еще не начал выполняться
                        $triggerStatuses[] = new TriggerStatusDto(
                            triggerId: $trigger->id,
                            triggerName: $trigger->name,
                            status: 'pending'
                        );
                        $hasRunningTriggers = true; // Считаем что процесс еще идет
                    }
                }
            }

            // Определяем общий статус права
            $overallStatus = 'completed';
            if ($hasRunningTriggers) {
                $overallStatus = 'processing';
            } elseif ($hasFailedTriggers) {
                $overallStatus = 'failed';
            }

            $result[$permissionId] = [
                'granted_permission_id' => $grantedPermission->id,
                'status' => $overallStatus,
                'event_type' => $eventType,
                'permission_name' => $grantedPermission->permission->name,
                'granted_permission_status' => $grantedPermission->status,
                'triggers' => array_map(fn($dto) => $dto->toArray(), $triggerStatuses),
            ];
        }

        return $result;
    }
}
