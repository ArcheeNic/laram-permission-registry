<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\PermissionScope;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionResource;

class PermissionChecker
{
    public function hasPermission(
        int $userId,
        string $service,
        string $permissionName,
        ?string $resourceExternalId = null,
    ): bool {
        $permission = Permission::where('service', $service)
            ->where('name', $permissionName)
            ->first();

        if (!$permission) {
            return false;
        }

        $resourceId = null;
        if (($permission->scope ?? PermissionScope::Service) === PermissionScope::Resource) {
            if ($resourceExternalId === null || $permission->resource_kind === null) {
                return false;
            }

            $resource = PermissionResource::findByExternal($service, $permission->resource_kind, $resourceExternalId);
            if (!$resource) {
                return false;
            }
            $resourceId = $resource->id;
        }

        $query = GrantedPermission::where('virtual_user_id', $userId)
            ->where('permission_id', $permission->id)
            ->where('enabled', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });

        if ($resourceId === null) {
            $query->whereNull('resource_id');
        } else {
            $query->where('resource_id', $resourceId);
        }

        return $query->exists();
    }
}
