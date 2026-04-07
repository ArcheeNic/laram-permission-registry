<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\TriggerFieldMapping as BaseTriggerFieldMapping;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TriggerFieldMapping extends BaseTriggerFieldMapping
{
    /**
     * Получить триггер, к которому относится маппинг
     */
    public function permissionTrigger(): BelongsTo
    {
        return $this->belongsTo(PermissionTrigger::class);
    }

    /**
     * Получить поле прав доступа
     */
    public function permissionField(): BelongsTo
    {
        return $this->belongsTo(PermissionField::class);
    }

    /**
     * Получить маппинг для триггера в виде массива
     */
    public static function getMappingForTrigger(int $permissionTriggerId): array
    {
        return self::with('permissionField')
            ->where(self::PERMISSION_TRIGGER_ID, $permissionTriggerId)
            ->get()
            ->mapWithKeys(fn($mapping) => [
                $mapping->{self::TRIGGER_FIELD_NAME} => [
                    'permission_field_id' => $mapping->{self::PERMISSION_FIELD_ID},
                    'permission_field_name' => $mapping->permissionField->name,
                    'is_internal' => $mapping->{self::IS_INTERNAL},
                ]
            ])
            ->toArray();
    }
}

