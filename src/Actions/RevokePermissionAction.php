<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Events\AfterPermissionRevoked;
use ArcheeNic\PermissionRegistry\Events\BeforePermissionRevoked;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use Illuminate\Support\Facades\Event;

class RevokePermissionAction
{
    public function handle(int $userId, int $permissionId): bool
    {
        $permission = Permission::findOrFail($permissionId);

        // Диспетчеризация события перед отзывом доступа
        Event::dispatch(new BeforePermissionRevoked(
            $userId,
            $permissionId,
            $permission->name,
            $permission->service
        ));

        // Поиск и отзыв выданного доступа
        $grantedPermission = GrantedPermission::where('user_id', $userId)
            ->where('permission_id', $permissionId)
            ->first();

        if (!$grantedPermission) {
            return false;
        }

        // Удаление записи (или деактивация, в зависимости от логики)
        $result = $grantedPermission->delete();

        // Диспетчеризация события после отзыва доступа
        Event::dispatch(new AfterPermissionRevoked(
            $userId,
            $permissionId,
            $permission->name,
            $permission->service
        ));

        return $result;
    }
}
