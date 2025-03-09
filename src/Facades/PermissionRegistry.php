<?php

namespace App\Modules\PermissionRegistry\Facades;

use App\Modules\PermissionRegistry\PermissionRegistryManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool hasPermission(int $userId, string $service, string $permissionName)
 * @method static bool validateField(int $userId, string $service, string $permissionName, string $fieldName, string $value)
 * @method static array getUserPermissions(int $userId, string $service = null)
 * @method static \App\Modules\PermissionRegistry\Models\GrantedPermission grantPermission(int $userId, string $service, string $permissionName, array $fieldValues = [], array $meta = [], string $expiresAt = null)
 * @method static bool revokePermission(int $userId, string $service, string $permissionName)
 * @method static void syncUserPermissions(int $userId)
 *
 * @see \App\Modules\PermissionRegistry\PermissionRegistryManager
 */
class PermissionRegistry extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'permission-registry';
    }
}
