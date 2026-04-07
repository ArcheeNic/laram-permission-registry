<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use Illuminate\Support\Collection;

/**
 * Получение значений глобальных полей виртуального пользователя
 */
class GetVirtualUserFieldValueAction
{
    /**
     * Получить значение конкретного глобального поля пользователя
     *
     * @param int $virtualUserId ID виртуального пользователя
     * @param int $fieldId ID поля
     * @return string|null
     */
    public function execute(int $virtualUserId, int $fieldId): ?string
    {
        $fieldValue = VirtualUserFieldValue::where(VirtualUserFieldValue::VIRTUAL_USER_ID, $virtualUserId)
            ->where(VirtualUserFieldValue::PERMISSION_FIELD_ID, $fieldId)
            ->first();

        return $fieldValue?->value;
    }

    /**
     * Получить все значения глобальных полей пользователя
     *
     * @param int $virtualUserId ID виртуального пользователя
     * @return Collection<int, VirtualUserFieldValue>
     */
    public function executeAll(int $virtualUserId): Collection
    {
        return VirtualUserFieldValue::where(VirtualUserFieldValue::VIRTUAL_USER_ID, $virtualUserId)
            ->with('field')
            ->get();
    }
}
