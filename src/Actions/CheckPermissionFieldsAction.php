<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Models\GrantedPermissionFieldValue;
use ArcheeNic\PermissionRegistry\Models\Permission;

class CheckPermissionFieldsAction
{
    /**
     * Проверяет значение поля доступа пользователя
     */
    public function validate(int $userId, string $service, string $permissionName, string $fieldName, string $value): bool
    {
        // Находим доступ
        $permission = Permission::where('service', $service)
            ->where('name', $permissionName)
            ->first();

        if (!$permission) {
            return false;
        }

        // Находим поле доступа
        $field = $permission->fields()
            ->where('name', $fieldName)
            ->first();

        if (!$field) {
            return false;
        }

        // Проверяем значение поля
        return GrantedPermissionFieldValue::whereHas('grantedPermission', function ($query) use ($userId, $permission) {
            $query->where('user_id', $userId)
                ->where('permission_id', $permission->id)
                ->where('enabled', true)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                });
        })
            ->where('permission_field_id', $field->id)
            ->where('value', $value)
            ->exists();
    }
}
