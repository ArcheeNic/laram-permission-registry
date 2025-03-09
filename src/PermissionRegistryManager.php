<?php

namespace ArcheeNic\PermissionRegistry;

use ArcheeNic\PermissionRegistry\Actions\CheckPermissionFieldsAction;
use ArcheeNic\PermissionRegistry\Actions\GetUserPermissionsAction;
use ArcheeNic\PermissionRegistry\Actions\GrantPermissionAction;
use ArcheeNic\PermissionRegistry\Actions\PermissionChecker;
use ArcheeNic\PermissionRegistry\Actions\RevokePermissionAction;
use ArcheeNic\PermissionRegistry\Actions\SyncUserPermissionsAction;
use ArcheeNic\PermissionRegistry\Models\Permission;

class PermissionRegistryManager
{
    protected PermissionChecker $permissionChecker;
    protected GrantPermissionAction $grantPermissionAction;
    protected RevokePermissionAction $revokePermissionAction;
    protected GetUserPermissionsAction $getUserPermissionsAction;
    protected CheckPermissionFieldsAction $checkPermissionFieldsAction;
    protected SyncUserPermissionsAction $syncUserPermissionsAction;

    public function __construct(
        PermissionChecker $permissionChecker,
        GrantPermissionAction $grantPermissionAction,
        RevokePermissionAction $revokePermissionAction,
        GetUserPermissionsAction $getUserPermissionsAction,
        CheckPermissionFieldsAction $checkPermissionFieldsAction,
        SyncUserPermissionsAction $syncUserPermissionsAction
    ) {
        $this->permissionChecker = $permissionChecker;
        $this->grantPermissionAction = $grantPermissionAction;
        $this->revokePermissionAction = $revokePermissionAction;
        $this->getUserPermissionsAction = $getUserPermissionsAction;
        $this->checkPermissionFieldsAction = $checkPermissionFieldsAction;
        $this->syncUserPermissionsAction = $syncUserPermissionsAction;
    }

    /**
     * Проверяет наличие доступа у пользователя
     */
    public function hasPermission(int $userId, string $service, string $permissionName): bool
    {
        return $this->permissionChecker->hasPermission($userId, $service, $permissionName);
    }

    /**
     * Проверяет значение поля доступа
     */
    public function validateField(int $userId, string $service, string $permissionName, string $fieldName, string $value): bool
    {
        return $this->checkPermissionFieldsAction->validate($userId, $service, $permissionName, $fieldName, $value);
    }

    /**
     * Получает все доступы пользователя
     */
    public function getUserPermissions(int $userId, string $service = null): array
    {
        return $this->getUserPermissionsAction->handle($userId, $service);
    }

    /**
     * Выдает доступ пользователю
     */
    public function grantPermission(
        int $userId,
        string $service,
        string $permissionName,
        array $fieldValues = [],
        array $meta = [],
        string $expiresAt = null
    ) {
        $permission = Permission::where('service', $service)
            ->where('name', $permissionName)
            ->firstOrFail();

        return $this->grantPermissionAction->handle($userId, $permission->id, $fieldValues, $meta, $expiresAt);
    }

    /**
     * Отзывает доступ у пользователя
     */
    public function revokePermission(int $userId, string $service, string $permissionName): bool
    {
        $permission = Permission::where('service', $service)
            ->where('name', $permissionName)
            ->first();

        if (!$permission) {
            return false;
        }

        return $this->revokePermissionAction->handle($userId, $permission->id);
    }

    /**
     * Синхронизирует доступы пользователя на основе его должностей и групп
     */
    public function syncUserPermissions(int $userId): void
    {
        $this->syncUserPermissionsAction->handle($userId);
    }
}