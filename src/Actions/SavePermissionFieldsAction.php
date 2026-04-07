<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\GrantedPermissionFieldValue;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;

class SavePermissionFieldsAction
{
    public function handle(GrantedPermission $grantedPermission, Permission $permission, array $fieldValues): void
    {
        foreach ($permission->fields as $field) {
            if ($field->is_global) {
                $this->saveGlobalField($grantedPermission->virtual_user_id, $field, $fieldValues);
            } else {
                $value = $fieldValues[$field->id] ?? $field->default_value;
                GrantedPermissionFieldValue::updateOrCreate(
                    [
                        'granted_permission_id' => $grantedPermission->id,
                        'permission_field_id' => $field->id,
                    ],
                    ['value' => $value]
                );
            }
        }
    }

    private function saveGlobalField(int $userId, PermissionField $field, array $fieldValues): void
    {
        if (!array_key_exists($field->id, $fieldValues)) {
            return;
        }

        $value = $fieldValues[$field->id];
        if ($value === null || $value === '') {
            return;
        }

        VirtualUserFieldValue::updateOrCreate(
            [
                VirtualUserFieldValue::VIRTUAL_USER_ID => $userId,
                VirtualUserFieldValue::PERMISSION_FIELD_ID => $field->id,
            ],
            [
                VirtualUserFieldValue::VALUE => $value,
                VirtualUserFieldValue::SOURCE => 'trigger',
            ]
        );
    }
}
