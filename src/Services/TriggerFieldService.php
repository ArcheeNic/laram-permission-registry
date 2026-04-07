<?php

namespace ArcheeNic\PermissionRegistry\Services;

use ArcheeNic\PermissionRegistry\Actions\GetVirtualUserMappedFieldsAction;
use Illuminate\Support\Collection;

/**
 * Сервис для работы с замапленными полями триггеров
 * 
 * Предоставляет удобные методы для получения полей с автоматической проверкой наличия
 */
class TriggerFieldService
{
    private ?Collection $cachedFields = null;
    private ?int $cachedVirtualUserId = null;
    private ?int $cachedTriggerId = null;

    public function __construct(
        private GetVirtualUserMappedFieldsAction $getMappedFieldsAction
    ) {
    }

    /**
     * Загрузить и закешировать поля для пользователя и триггера
     *
     * @param int $virtualUserId ID виртуального пользователя
     * @param int $triggerId ID триггера
     * @param array $internal Фильтр по is_internal
     * @return Collection Коллекция с ключами trigger_field_name и значениями ['value' => ..., 'id' => ...]
     */
    public function load(int $virtualUserId, int $triggerId, array $internal = [true, false]): Collection
    {
        $this->cachedFields = $this->getMappedFieldsAction->execute($virtualUserId, $triggerId, $internal);
        $this->cachedVirtualUserId = $virtualUserId;
        $this->cachedTriggerId = $triggerId;

        return $this->cachedFields;
    }

    /**
     * Перезагрузить закешированные поля
     *
     * @param array $internal Фильтр по is_internal
     * @return Collection Коллекция с ключами trigger_field_name и значениями ['value' => ..., 'id' => ...]
     * @throws \RuntimeException Если поля не были загружены
     */
    public function reload(array $internal = [true, false]): Collection
    {
        if ($this->cachedVirtualUserId === null || $this->cachedTriggerId === null) {
            throw new \RuntimeException('Невозможно перезагрузить поля: они не были загружены. Используйте метод load() сначала.');
        }

        return $this->load($this->cachedVirtualUserId, $this->cachedTriggerId, $internal);
    }

    /**
     * Получить все замапленные поля для пользователя и триггера
     * Использует кеш, если поля уже были загружены для этого пользователя и триггера
     *
     * @param int $virtualUserId ID виртуального пользователя
     * @param int $triggerId ID триггера
     * @param array $internal Фильтр по is_internal: [true, false] - все, [true] - только внутренние, [false] - только внешние
     * @return Collection Коллекция с ключами trigger_field_name и значениями ['value' => ..., 'id' => ...]
     */
    public function getMappedFields(int $virtualUserId, int $triggerId, array $internal = [true, false]): Collection
    {
        // Проверяем, есть ли закешированные данные для этого пользователя и триггера
        if ($this->cachedFields !== null 
            && $this->cachedVirtualUserId === $virtualUserId 
            && $this->cachedTriggerId === $triggerId) {
            return $this->cachedFields;
        }

        // Загружаем и кешируем
        return $this->load($virtualUserId, $triggerId, $internal);
    }

    /**
     * Получить конкретное поле из коллекции или выбросить исключение
     *
     * @param Collection $mappedFields Коллекция полей
     * @param string $fieldName Название поля
     * @return array ['value' => ..., 'id' => ...]
     * @throws \RuntimeException Если поле не найдено
     */
    public function getRequiredFieldFromCollection(Collection $mappedFields, string $fieldName): array
    {
        if (!isset($mappedFields[$fieldName])) {
            throw new \RuntimeException(
                "Поле '{$fieldName}' не найдено в маппинге триггера. Проверьте конфигурацию маппинга полей."
            );
        }

        return $mappedFields[$fieldName];
    }

    /**
     * Получить только ID поля из коллекции
     *
     * @param Collection $mappedFields Коллекция полей
     * @param string $fieldName Название поля
     * @return int ID поля
     * @throws \RuntimeException Если поле не найдено
     */
    public function getRequiredFieldIdFromCollection(Collection $mappedFields, string $fieldName): int
    {
        $field = $this->getRequiredFieldFromCollection($mappedFields, $fieldName);
        return $field['id'];
    }

    /**
     * Получить только значение поля из коллекции
     *
     * @param Collection $mappedFields Коллекция полей
     * @param string $fieldName Название поля
     * @return mixed Значение поля
     * @throws \RuntimeException Если поле не найдено
     */
    public function getRequiredFieldValueFromCollection(Collection $mappedFields, string $fieldName): mixed
    {
        $field = $this->getRequiredFieldFromCollection($mappedFields, $fieldName);
        return $field['value'];
    }

    /**
     * Получить значение опционального поля из коллекции (null, если поля нет)
     *
     * @param Collection $mappedFields Коллекция полей
     * @param string $fieldName Название поля
     * @return mixed Значение поля или null
     */
    public function getFieldValueFromCollection(Collection $mappedFields, string $fieldName): mixed
    {
        if (!isset($mappedFields[$fieldName])) {
            return null;
        }
        $field = $mappedFields[$fieldName];
        return $field['value'] ?? null;
    }

    /**
     * Получить конкретное поле или выбросить исключение
     *
     * @param int $virtualUserId ID виртуального пользователя
     * @param int $triggerId ID триггера
     * @param string $fieldName Название поля
     * @return array ['value' => ..., 'id' => ...]
     * @throws \RuntimeException Если поле не найдено
     */
    public function getRequiredField(int $virtualUserId, int $triggerId, string $fieldName): array
    {
        $mappedFields = $this->getMappedFields($virtualUserId, $triggerId);
        return $this->getRequiredFieldFromCollection($mappedFields, $fieldName);
    }

    /**
     * Получить только ID поля
     *
     * @param int $virtualUserId ID виртуального пользователя
     * @param int $triggerId ID триггера
     * @param string $fieldName Название поля
     * @return int ID поля
     * @throws \RuntimeException Если поле не найдено
     */
    public function getRequiredFieldId(int $virtualUserId, int $triggerId, string $fieldName): int
    {
        $field = $this->getRequiredField($virtualUserId, $triggerId, $fieldName);
        return $field['id'];
    }

    /**
     * Получить только значение поля
     *
     * @param int $virtualUserId ID виртуального пользователя
     * @param int $triggerId ID триггера
     * @param string $fieldName Название поля
     * @return mixed Значение поля
     * @throws \RuntimeException Если поле не найдено
     */
    public function getRequiredFieldValue(int $virtualUserId, int $triggerId, string $fieldName): mixed
    {
        $field = $this->getRequiredField($virtualUserId, $triggerId, $fieldName);
        return $field['value'];
    }
}
