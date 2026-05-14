<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Events\ValidatingGlobalFields;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;

/**
 * Обновление глобальных полей виртуального пользователя
 */
class UpdateVirtualUserGlobalFieldsAction
{
    public function __construct(
        private GenerateDisplayNameAction $generateDisplayNameAction,
        private GetVirtualUserFieldValueAction $getVirtualUserFieldValueAction
    ) {}

    /**
     * @param  array<int, string|null>  $fields  ['field_id' => 'value']
     * @param  array<int, array<string, mixed>>  $fieldsMeta  ['field_id' => ['meta_key' => mixed]]
     */
    public function execute(int $virtualUserId, array $fields, array $fieldsMeta = []): void
    {
        event(new ValidatingGlobalFields($virtualUserId, $fields, $fieldsMeta));

        $existingFields = $this->getVirtualUserFieldValueAction->executeAll($virtualUserId);
        $processedFieldIds = [];

        foreach ($existingFields as $existingField) {
            if (! array_key_exists($existingField->permission_field_id, $fields)) {
                continue;
            }

            $processedFieldIds[] = $existingField->permission_field_id;

            if ($fields[$existingField->permission_field_id] === null) {
                $existingField->delete();

                continue;
            }

            $attributes = [VirtualUserFieldValue::VALUE => $fields[$existingField->permission_field_id]];

            if (array_key_exists($existingField->permission_field_id, $fieldsMeta)) {
                $attributes[VirtualUserFieldValue::META] = $this->normalizeMeta($fieldsMeta[$existingField->permission_field_id]);
            }

            $existingField->update($attributes);
        }

        foreach ($fields as $fieldId => $value) {
            if (in_array($fieldId, $processedFieldIds) || $value === null) {
                continue;
            }

            $attributes = [
                VirtualUserFieldValue::VIRTUAL_USER_ID => $virtualUserId,
                VirtualUserFieldValue::PERMISSION_FIELD_ID => $fieldId,
                VirtualUserFieldValue::VALUE => $value,
            ];

            if (array_key_exists($fieldId, $fieldsMeta)) {
                $attributes[VirtualUserFieldValue::META] = $this->normalizeMeta($fieldsMeta[$fieldId]);
            }

            VirtualUserFieldValue::create($attributes);
        }

        $displayName = $this->generateDisplayNameAction->execute($virtualUserId);

        $user = VirtualUser::find($virtualUserId);
        if ($user) {
            $user->update(['name' => $displayName]);
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function normalizeMeta(array $meta): ?array
    {
        $filtered = array_filter($meta, static fn ($value) => $value !== null);

        return $filtered === [] ? null : $filtered;
    }
}
