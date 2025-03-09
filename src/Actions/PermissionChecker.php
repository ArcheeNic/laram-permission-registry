<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;

class PermissionChecker
{
    /**
     * Проверяет наличие записи о доступе у пользователя (без реальной проверки прав)
     * Используется только для отображения информации, а не для контроля доступа
     */
    public function hasPermission(int $userId, string $service, string $permissionName): bool
    {
        $permission = Permission::where('service', $service)
            ->where('name', $permissionName)
            ->first();

        if (!$permission) {
            return false;
        }

        return GrantedPermission::where('user_id', $userId)
            ->where('permission_id', $permission->id)
            ->where('enabled', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}
