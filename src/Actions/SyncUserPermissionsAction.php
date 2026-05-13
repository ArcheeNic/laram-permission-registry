<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\PermissionScope;
use ArcheeNic\PermissionRegistry\Events\AfterPermissionGranted;
use ArcheeNic\PermissionRegistry\Events\BeforePermissionGranted;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\GrantedPermissionFieldValue;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Models\VirtualUserGroup;
use ArcheeNic\PermissionRegistry\Models\VirtualUserPosition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class SyncUserPermissionsAction
{
    /**
     * Синхронизирует доступы пользователя на основе его должностей и групп.
     * Учитываются только service-scope; resource-scoped права выдаются вручную с указанием ресурса.
     */
    public function handle(int $userId): void
    {
        $requiredPermissions = $this->getRequiredPermissions($userId);

        $currentPermissions = GrantedPermission::where('virtual_user_id', $userId)
            ->where('enabled', true)
            ->whereNull('resource_id')
            ->pluck('permission_id')
            ->toArray();

        $permissionsToGrant = array_diff($requiredPermissions, $currentPermissions);
        foreach ($permissionsToGrant as $permissionId) {
            $permission = Permission::find($permissionId);
            if (! $permission) {
                continue;
            }
            if (($permission->scope ?? PermissionScope::Service) === PermissionScope::Resource) {
                Log::warning('Skipping resource-scoped permission in user sync', [
                    'permission_id' => $permissionId,
                    'virtual_user_id' => $userId,
                ]);
                continue;
            }
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
        $positionIds = VirtualUserPosition::where('virtual_user_id', $userId)
            ->pluck('position_id')
            ->toArray();

        foreach ($positionIds as $positionId) {
            $this->addPositionPermissions($positionId, $permissionIds);
        }

        // Доступы из групп
        $groupIds = VirtualUserGroup::where('virtual_user_id', $userId)
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

        // Создание записи о выданном доступе (service-scope, resource_id всегда NULL)
        $grantedPermission = GrantedPermission::create([
            'virtual_user_id' => $userId,
            'permission_id' => $permissionId,
            'resource_id' => null,
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
