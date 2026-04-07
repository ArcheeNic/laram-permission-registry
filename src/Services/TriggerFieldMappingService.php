<?php

namespace ArcheeNic\PermissionRegistry\Services;

use ArcheeNic\PermissionRegistry\Models\TriggerFieldMapping;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use Illuminate\Support\Facades\Cache;

class TriggerFieldMappingService
{
    /**
     * Получить маппинг полей для триггера
     *
     * @param int $permissionTriggerId
     * @return array Массив вида ['trigger_field' => 'global_field']
     */
    public function getMapping(int $permissionTriggerId): array
    {
        $cacheKey = "trigger_field_mapping_{$permissionTriggerId}";

        return Cache::remember($cacheKey, 3600, function () use ($permissionTriggerId) {
            return TriggerFieldMapping::getMappingForTrigger($permissionTriggerId);
        });
    }

    /**
     * Очистить кеш маппинга для триггера
     *
     * @param int $permissionTriggerId
     * @return void
     */
    public function clearCache(int $permissionTriggerId): void
    {
        $cacheKey = "trigger_field_mapping_{$permissionTriggerId}";
        Cache::forget($cacheKey);
    }

    /**
     * Применить маппинг к глобальным полям
     * Преобразует глобальные поля согласно маппингу триггера
     *
     * @param int $virtualUserId ID виртуального пользователя
     * @param array $mapping Маппинг триггера
     * @return array Преобразованные поля
     */
    public function applyMapping(int $virtualUserId, array $mapping): array
    {
        if (empty($mapping)) {
            return [];
        }

        $mappedFields = [];

        // Получить все глобальные поля пользователя по ID
        $userFieldValues = VirtualUserFieldValue::where(VirtualUserFieldValue::VIRTUAL_USER_ID, $virtualUserId)
            ->pluck(VirtualUserFieldValue::VALUE, VirtualUserFieldValue::PERMISSION_FIELD_ID)
            ->toArray();

        foreach ($mapping as $triggerFieldName => $mappingData) {
            $permissionFieldId = $mappingData['permission_field_id'];
            $isInternal = $mappingData['is_internal'];

            if ($isInternal) {
                // Для внутренних полей передаем ID поля, куда сохранить
                $mappedFields[$triggerFieldName] = $permissionFieldId;
            } else {
                // Для входящих полей передаем значение из VirtualUserFieldValue
                if (isset($userFieldValues[$permissionFieldId])) {
                    $mappedFields[$triggerFieldName] = $userFieldValues[$permissionFieldId];
                }
            }
        }

        return $mappedFields;
    }
}

