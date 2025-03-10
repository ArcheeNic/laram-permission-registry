<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Events\AfterPermissionGranted;
use ArcheeNic\PermissionRegistry\Events\BeforePermissionGranted;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\GrantedPermissionFieldValue;
use ArcheeNic\PermissionRegistry\Models\Permission;
use Illuminate\Support\Facades\Event;

class GrantPermissionAction
{
    public function handle(
        int $userId,
        int $permissionId,
        array $fieldValues = [],
        array $meta = [],
        ?string $expiresAt = null
    ): GrantedPermission {
        $permission = Permission::findOrFail($permissionId);

        // Диспетчеризация события перед выдачей доступа
        Event::dispatch(new BeforePermissionGranted(
            $userId,
            $permissionId,
            $permission->name,
            $permission->service,
            $fieldValues
        ));

        // Создание или обновление записи о выданном доступе
        $grantedPermission = GrantedPermission::updateOrCreate(
            [
                'user_id' => $userId,
                'permission_id' => $permissionId,
            ],
            [
                'enabled' => true, // Всегда устанавливаем enabled в true при выдаче доступа
                'meta' => $meta,
                'granted_at' => now(),
                'expires_at' => $expiresAt,
            ]
        );

        // Создание значений полей доступа
        $permissionFields = $permission->fields;

        foreach ($permissionFields as $field) {
            $value = $fieldValues[$field->id] ?? $field->default_value;

            GrantedPermissionFieldValue::updateOrCreate(
                [
                    'granted_permission_id' => $grantedPermission->id,
                    'permission_field_id' => $field->id,
                ],
                [
                    'value' => $value,
                ]
            );
        }

        // Диспетчеризация события после выдачи доступа
        Event::dispatch(new AfterPermissionGranted(
            $userId,
            $permissionId,
            $permission->name,
            $permission->service
        ));

        return $grantedPermission;
    }
}
