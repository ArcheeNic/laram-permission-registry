<?php

namespace ArcheeNic\PermissionRegistry\Actions;

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
     */
    public function execute(int $virtualUserId, array $fields): void
    {
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

            $existingField->update([VirtualUserFieldValue::VALUE => $fields[$existingField->permission_field_id]]);
        }

        foreach ($fields as $fieldId => $value) {
            if (in_array($fieldId, $processedFieldIds) || $value === null) {
                continue;
            }

            VirtualUserFieldValue::create([
                VirtualUserFieldValue::VIRTUAL_USER_ID => $virtualUserId,
                VirtualUserFieldValue::PERMISSION_FIELD_ID => $fieldId,
                VirtualUserFieldValue::VALUE => $value,
            ]);
        }

        $displayName = $this->generateDisplayNameAction->execute($virtualUserId);

        $user = VirtualUser::find($virtualUserId);
        if ($user) {
            $user->update(['name' => $displayName]);
        }
    }
}
