<?php

namespace App\Modules\PermissionRegistry\Actions;

use App\Modules\PermissionRegistry\Events\AfterPermissionGranted;
use App\Modules\PermissionRegistry\Events\BeforePermissionGranted;
use App\Modules\PermissionRegistry\Models\GrantedPermission;
use App\Modules\PermissionRegistry\Models\GrantedPermissionFieldValue;
use App\Modules\PermissionRegistry\Models\Permission;
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

        // Убеждаемся, что виртуальный пользователь существует
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
                'enabled' => true,
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
