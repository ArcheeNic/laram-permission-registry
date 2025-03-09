<?php

namespace App\Modules\PermissionRegistry\Actions;

use App\Modules\PermissionRegistry\Events\AfterPermissionGranted;
use App\Modules\PermissionRegistry\Events\BeforePermissionGranted;
use App\Modules\PermissionRegistry\Models\GrantedPermission;
use App\Modules\PermissionRegistry\Models\GrantedPermissionFieldValue;
use App\Modules\PermissionRegistry\Models\Permission;
use App\Modules\PermissionRegistry\Models\PermissionGroup;
use App\Modules\PermissionRegistry\Models\Position;
use App\Modules\PermissionRegistry\Models\UserGroup;
use App\Modules\PermissionRegistry\Models\UserPosition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;

class SyncUserPermissionsAction
{
    /**
     * Синхронизирует доступы пользователя на основе его должностей и групп
     */
    public function handle(int $userId): void
    {
        // Определяем, какие доступы должны быть у пользователя
        $requiredPermissions = $this->getRequiredPermissions($userId);

        // Получаем текущие доступы пользователя
        $currentPermissions = GrantedPermission::where('user_id', $userId)
            ->where('enabled', true)
            ->pluck('permission_id')
            ->toArray();

        // Выдаем новые доступы
        $permissionsToGrant = array_diff($requiredPermissions, $currentPermissions);
        foreach ($permissionsToGrant as $permissionId) {
            $this->grantPermission($userId, $permissionId);
        }
    }

    /**
     * Определяет, какие доступы должны быть у пользователя на основе должностей и групп
     */
    private function getRequiredPermissions(int $userId): array
    {
        $permissionIds = [];

        // Доступы из должностей
        $positionIds = UserPosition::where('user_id', $userId)
            ->pluck('position_id')
            ->toArray();

        foreach ($positionIds as $positionId) {
            $this->addPositionPermissions($positionId, $permissionIds);
        }

        // Доступы из групп
        $groupIds = UserGroup::where('user_id', $userId)
            ->pluck('permission_group_id')
            ->toArray();

        foreach ($groupIds as $groupId) {
            $this->addGroupPermissions($groupId, $permissionIds);
        }

        return array_unique($permissionIds);
    }

    /**
     * Добавляет доступы из должности и её родительских должностей
     */
    private function addPositionPermissions(int $positionId, array &$permissionIds, array $processedPositions = []): void
    {
        // Избегаем бесконечных циклов
        if (in_array($positionId, $processedPositions)) {
            return;
        }

        $processedPositions[] = $positionId;

        $position = Position::with(['permissions', 'groups', 'parent'])->find($positionId);

        if (!$position) {
            return;
        }

        // Добавляем прямые доступы из должности
        foreach ($position->permissions as $permission) {
            $permissionIds[] = $permission->id;
        }

        // Добавляем доступы из групп должности
        foreach ($position->groups as $group) {
            $this->addGroupPermissions($group->id, $permissionIds);
        }

        // Рекурсивно добавляем доступы из родительской должности
        if ($position->parent) {
            $this->addPositionPermissions($position->parent->id, $permissionIds, $processedPositions);
        }
    }

    /**
     * Добавляет доступы из группы
     */
    private function addGroupPermissions(int $groupId, array &$permissionIds): void
    {
        $permissions = PermissionGroup::find($groupId)->permissions ?? collect();

        foreach ($permissions as $permission) {
            $permissionIds[] = $permission->id;
        }
    }

    /**
     * Выдает доступ пользователю
     */
    private function grantPermission(int $userId, int $permissionId): GrantedPermission
    {
        $permission = Permission::findOrFail($permissionId);

        // Диспетчеризация события перед выдачей доступа
        Event::dispatch(new BeforePermissionGranted(
            $userId,
            $permissionId,
            $permission->name,
            $permission->service
        ));

        // Создание записи о выданном доступе
        $grantedPermission = GrantedPermission::create([
            'user_id' => $userId,
            'permission_id' => $permissionId,
            'enabled' => true,
            'granted_at' => now(),
        ]);

        // Создание значений полей доступа
        $permissionFields = $permission->fields;

        foreach ($permissionFields as $field) {
            GrantedPermissionFieldValue::create([
                'granted_permission_id' => $grantedPermission->id,
                'permission_field_id' => $field->id,
                'value' => $field->default_value,
            ]);
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
