<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\TriggerFieldMapping;
use ArcheeNic\PermissionRegistry\Services\TriggerFieldMappingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaveTriggerFieldMappingAction
{
    public function __construct(
        private TriggerFieldMappingService $triggerFieldMappingService
    ) {
    }

    /**
     * Сохранить маппинг полей для триггера
     *
     * @param int $permissionTriggerId
     * @param array $mapping Массив вида ['trigger_field' => 'field_name_or_id']
     * @param array $internalMapping Массив вида ['trigger_field' => 'field_name_or_id'] для внутренних полей
     * @return bool
     */
    public function handle(int $permissionTriggerId, array $mapping, array $internalMapping = []): bool
    {
        try {
            DB::transaction(function () use ($permissionTriggerId, $mapping, $internalMapping) {
                // Удалить существующий маппинг
                TriggerFieldMapping::where(
                    TriggerFieldMapping::PERMISSION_TRIGGER_ID,
                    $permissionTriggerId
                )->delete();

                // Создать новый маппинг
                $mappingsToInsert = [];
                
                // Обработать обычные поля
                foreach ($mapping as $triggerField => $fieldNameOrId) {
                    // Пропустить пустые значения
                    if (empty($fieldNameOrId)) {
                        continue;
                    }

                    $permissionFieldId = $this->resolvePermissionFieldId($fieldNameOrId);
                    
                    if (!$permissionFieldId) {
                        continue;
                    }

                    $mappingsToInsert[] = [
                        TriggerFieldMapping::PERMISSION_TRIGGER_ID => $permissionTriggerId,
                        TriggerFieldMapping::TRIGGER_FIELD_NAME => $triggerField,
                        TriggerFieldMapping::PERMISSION_FIELD_ID => $permissionFieldId,
                        TriggerFieldMapping::IS_INTERNAL => false,
                        TriggerFieldMapping::CREATED_AT => now(),
                        TriggerFieldMapping::UPDATED_AT => now(),
                    ];
                }

                // Обработать внутренние поля
                foreach ($internalMapping as $triggerField => $fieldNameOrId) {
                    // Пропустить пустые значения
                    if (empty($fieldNameOrId)) {
                        continue;
                    }

                    $permissionFieldId = $this->resolvePermissionFieldId($fieldNameOrId);
                    
                    if (!$permissionFieldId) {
                        continue;
                    }

                    $mappingsToInsert[] = [
                        TriggerFieldMapping::PERMISSION_TRIGGER_ID => $permissionTriggerId,
                        TriggerFieldMapping::TRIGGER_FIELD_NAME => $triggerField,
                        TriggerFieldMapping::PERMISSION_FIELD_ID => $permissionFieldId,
                        TriggerFieldMapping::IS_INTERNAL => true,
                        TriggerFieldMapping::CREATED_AT => now(),
                        TriggerFieldMapping::UPDATED_AT => now(),
                    ];
                }

                if (!empty($mappingsToInsert)) {
                    TriggerFieldMapping::insert($mappingsToInsert);
                }
            });
            $this->triggerFieldMappingService->clearCache($permissionTriggerId);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to save trigger field mapping', [
                'permission_trigger_id' => $permissionTriggerId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Получить ID поля по имени или ID
     *
     * @param string|int $fieldNameOrId
     * @return int|null
     */
    private function resolvePermissionFieldId(string|int $fieldNameOrId): ?int
    {
        // Если передан ID
        if (is_numeric($fieldNameOrId)) {
            return (int) $fieldNameOrId;
        }

        // Если передано имя - найти ID
        $field = PermissionField::where(PermissionField::NAME, $fieldNameOrId)->first();
        
        return $field?->id;
    }
}

