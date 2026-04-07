<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Models\TriggerFieldMapping;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

/**
 * Получение замапленных полей виртуального пользователя для триггера
 * 
 * Выполняет join между TriggerFieldMapping и VirtualUserFieldValue
 * для получения значений полей пользователя, замапленных на поля триггера
 */
class GetVirtualUserMappedFieldsAction
{
    /**
     * Получить замапленные значения полей для пользователя и триггера
     *
     * @param int $virtualUserId ID виртуального пользователя
     * @param int $permissionTriggerId ID триггера
     * @param array $internal Фильтр по is_internal: [true, false] - все, [true] - только внутренние, [false] - только внешние
     * @return Collection Коллекция с ключами trigger_field_name и значениями ['value' => ..., 'id' => ...]
     */
    public function execute(int $virtualUserId, int $permissionTriggerId, array $internal = [true, false]): Collection
    {
        $tfm = new TriggerFieldMapping();
        $vufv = new VirtualUserFieldValue();

        return TriggerFieldMapping::query()
            ->select([
                $tfm->qualifyColumn(TriggerFieldMapping::TRIGGER_FIELD_NAME),
                $vufv->qualifyColumn(VirtualUserFieldValue::VALUE),
                $tfm->qualifyColumn(TriggerFieldMapping::PERMISSION_FIELD_ID)
            ])
            ->leftJoin(
                $vufv->getTable(),
                function (JoinClause $join) use ($tfm, $vufv, $virtualUserId) {
                    $join->on(
                        $tfm->qualifyColumn(TriggerFieldMapping::PERMISSION_FIELD_ID),
                        '=',
                        $vufv->qualifyColumn(VirtualUserFieldValue::PERMISSION_FIELD_ID)
                    )->where($vufv->qualifyColumn(VirtualUserFieldValue::VIRTUAL_USER_ID), '=', $virtualUserId);
                }
            )
            ->where($tfm->qualifyColumn(TriggerFieldMapping::PERMISSION_TRIGGER_ID), $permissionTriggerId)
            ->whereIn($tfm->qualifyColumn(TriggerFieldMapping::IS_INTERNAL), $internal)
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->{TriggerFieldMapping::TRIGGER_FIELD_NAME} => [
                    'value' => $item->{VirtualUserFieldValue::VALUE},
                    'id' => $item->{TriggerFieldMapping::PERMISSION_FIELD_ID}
                ]];
            });
    }
}
