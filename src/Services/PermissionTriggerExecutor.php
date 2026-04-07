<?php

namespace ArcheeNic\PermissionRegistry\Services;

use ArcheeNic\PermissionRegistry\Contracts\PermissionTriggerInterface;
use ArcheeNic\PermissionRegistry\Enums\ExecutionLogStatus;
use ArcheeNic\PermissionRegistry\Enums\GrantedPermissionStatus;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\PermissionExecutionLog;
use ArcheeNic\PermissionRegistry\Models\PermissionTriggerAssignment;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use ArcheeNic\PermissionRegistry\ValueObjects\TriggerContext;
use ArcheeNic\PermissionRegistry\ValueObjects\TriggerResult;
use Illuminate\Support\Facades\Log;

class PermissionTriggerExecutor
{
    public function __construct(
        private TriggerFieldMappingService $mappingService
    ) {
    }
    /**
     * Выполнить цепочку триггеров для выданного права
     */
    public function executeChain(GrantedPermission $grantedPermission, string $eventType): bool
    {
        $permission = $grantedPermission->permission;

        // Получить отсортированные триггеры для данного события
        $assignments = $permission->triggerAssignments()
            ->where('event_type', $eventType)
            ->where('is_enabled', true)
            ->with('trigger')
            ->orderBy('order')
            ->get();

        if ($assignments->isEmpty()) {
            // Нет триггеров - считаем успешным завершением
            $grantedPermission->update([
                'status' => $eventType === 'grant' 
                    ? GrantedPermissionStatus::GRANTED->value 
                    : GrantedPermissionStatus::REVOKED->value,
            ]);
            return true;
        }

        // Обновить статус на "в процессе"
        $grantedPermission->update([
            'status' => $eventType === 'grant' 
                ? GrantedPermissionStatus::GRANTING->value 
                : GrantedPermissionStatus::REVOKING->value,
        ]);

        $allSuccess = true;
        $failedAssignment = null;
        $failedIndex = null;
        $processedCount = 0;

        foreach ($assignments as $index => $assignment) {
            try {
                $context = $this->buildContext(
                    $grantedPermission,
                    $assignment->permission_trigger_id,
                    [],
                    $assignment->config ?? []
                );
                $result = $this->processTrigger($assignment, $context);

                if (!$result->success) {
                    $allSuccess = false;
                    $failedAssignment = $assignment;
                    $failedIndex = $index;
                    break;
                }

                $processedCount++;
            } catch (\Exception $e) {
                Log::error('Trigger execution failed', [
                    'trigger_id' => $assignment->permission_trigger_id,
                    'granted_permission_id' => $grantedPermission->id,
                    'error' => $e->getMessage(),
                ]);
                $allSuccess = false;
                $failedAssignment = $assignment;
                $failedIndex = $index;
                break;
            }
        }

        $this->updateFinalStatus(
            $grantedPermission,
            $allSuccess,
            $eventType,
            $processedCount,
            $failedAssignment,
            $failedIndex
        );

        return $allSuccess;
    }

    /**
     * Выполнить цепочку триггеров начиная с указанного индекса
     *
     * @param GrantedPermission $grantedPermission
     * @param \Illuminate\Support\Collection $assignments
     * @param int $startIndex
     * @param string $eventType
     * @return bool
     */
    public function executeChainFromTrigger(
        GrantedPermission $grantedPermission,
        $assignments,
        int $startIndex,
        string $eventType,
        array $manualFieldValues = []
    ): bool {
        // Обновить статус на "в процессе"
        $grantedPermission->update([
            'status' => $eventType === 'grant'
                ? GrantedPermissionStatus::GRANTING->value
                : GrantedPermissionStatus::REVOKING->value,
        ]);

        $allSuccess = true;
        $failedAssignment = null;
        $failedIndex = null;
        $processedCount = $startIndex;

        for ($i = $startIndex; $i < count($assignments); $i++) {
            $assignment = $assignments[$i];
            try {
                $context = $this->buildContext(
                    $grantedPermission,
                    $assignment->permission_trigger_id,
                    $i === $startIndex ? $manualFieldValues : [],
                    $assignment->config ?? []
                );
                $result = $this->processTrigger($assignment, $context);

                if (!$result->success) {
                    $allSuccess = false;
                    $failedAssignment = $assignment;
                    $failedIndex = $i;
                    break;
                }

                $processedCount++;
            } catch (\Exception $e) {
                Log::error('Trigger execution failed', [
                    'trigger_id' => $assignment->permission_trigger_id,
                    'granted_permission_id' => $grantedPermission->id,
                    'error' => $e->getMessage(),
                ]);
                $allSuccess = false;
                $failedAssignment = $assignment;
                $failedIndex = $i;
                break;
            }
        }

        $this->updateFinalStatus(
            $grantedPermission,
            $allSuccess,
            $eventType,
            $processedCount,
            $failedAssignment,
            $failedIndex
        );

        return $allSuccess;
    }

    private function updateFinalStatus(
        GrantedPermission $grantedPermission,
        bool $allSuccess,
        string $eventType,
        int $processedCount,
        ?PermissionTriggerAssignment $failedAssignment,
        ?int $failedIndex
    ): void {
        if ($allSuccess) {
            $grantedPermission->update([
                'status' => $eventType === 'grant'
                    ? GrantedPermissionStatus::GRANTED->value
                    : GrantedPermissionStatus::REVOKED->value,
                'status_message' => null,
                'meta' => array_merge($grantedPermission->meta ?? [], [
                    'last_failed_trigger_id' => null,
                    'last_failed_event_type' => null,
                    'failed_at_index' => null,
                ]),
            ]);

            return;
        }

        $triggerName = $failedAssignment?->trigger?->name ?? 'Unknown';
        $failStatus = $this->resolveFailStatus($eventType, $processedCount);

        $grantedPermission->update([
            'status' => $failStatus->value,
            'status_message' => "Ошибка в триггере: {$triggerName}",
            'meta' => array_merge($grantedPermission->meta ?? [], [
                'last_failed_trigger_id' => $failedAssignment?->permission_trigger_id,
                'last_failed_event_type' => $eventType,
                'failed_at_index' => $failedIndex,
            ]),
        ]);
    }

    private function resolveFailStatus(string $eventType, int $processedCount): GrantedPermissionStatus
    {
        if ($eventType === 'grant') {
            return GrantedPermissionStatus::PARTIALLY_GRANTED;
        }

        return $processedCount > 0
            ? GrantedPermissionStatus::PARTIALLY_REVOKED
            : GrantedPermissionStatus::FAILED;
    }

    /**
     * Выполнить отдельный триггер
     */
    private function processTrigger(PermissionTriggerAssignment $assignment, TriggerContext $context): TriggerResult
    {
        $trigger = $assignment->trigger;

        // Создать лог выполнения
        $log = PermissionExecutionLog::create([
            'granted_permission_id' => $context->grantedPermission->id,
            'permission_trigger_id' => $trigger->id,
            'event_type' => $assignment->event_type,
            'status' => ExecutionLogStatus::PENDING->value,
        ]);

        try {
            // Обновить статус на "выполняется"
            $log->update([
                'status' => ExecutionLogStatus::RUNNING->value,
                'started_at' => now(),
            ]);

            // Пауза для визуализации статуса (только для разработки)
            // if (config('app.debug')) {
                // sleep(5);
            // }

            // Инстанцировать триггер
            $triggerInstance = $this->instantiateTrigger($trigger->class_name);

            // Выполнить триггер
            $result = $triggerInstance->execute($context);

            // Сохранить результат
            $log->update([
                'status' => $result->success ? ExecutionLogStatus::SUCCESS->value : ExecutionLogStatus::FAILED->value,
                'completed_at' => now(),
                'error_message' => $result->errorMessage,
                'meta' => $result->meta,
            ]);

            return $result;
        } catch (\Exception $e) {
            $log->update([
                'status' => ExecutionLogStatus::FAILED->value,
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            return TriggerResult::failure($e->getMessage());
        }
    }

    /**
     * Построить контекст для выполнения триггера
     *
     * @param array $manualFieldValues значения для ручного продолжения шага (по имени поля триггера, напр. first_name, password)
     * @param array $assignmentConfig системные настройки экземпляра триггера (из PermissionTriggerAssignment.config)
     */
    private function buildContext(
        GrantedPermission $grantedPermission,
        int $permissionTriggerId,
        array $manualFieldValues = [],
        array $assignmentConfig = []
    ): TriggerContext {
        $permission = $grantedPermission->permission;
        $virtualUserId = $grantedPermission->virtual_user_id;

        // Получить значения специфичных полей
        $fieldValues = $grantedPermission->fieldValues()
            ->pluck('value', 'permission_field_id')
            ->toArray();

        // Применить маппинг полей для данного триггера и подмешать ручные значения (продолжение упавшего шага)
        $mapping = $this->mappingService->getMapping($permissionTriggerId);
        $mappedGlobalFields = $this->mappingService->applyMapping($virtualUserId, $mapping);
        $globalFields = array_merge($mappedGlobalFields, $manualFieldValues);

        return new TriggerContext(
            virtualUserId: $virtualUserId,
            permission: $permission,
            permissionTriggerId: $permissionTriggerId,
            fieldValues: $fieldValues,
            globalFields: $globalFields,
            grantedPermission: $grantedPermission,
            config: $assignmentConfig
        );
    }

    /**
     * Создать экземпляр триггера
     */
    private function instantiateTrigger(string $className): PermissionTriggerInterface
    {
        if (!class_exists($className)) {
            throw new \RuntimeException("Trigger class not found: {$className}");
        }

        $instance = app($className);

        if (!$instance instanceof PermissionTriggerInterface) {
            throw new \RuntimeException("Trigger must implement PermissionTriggerInterface: {$className}");
        }

        return $instance;
    }
}
