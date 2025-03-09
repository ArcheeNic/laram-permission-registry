<?php

namespace App\Modules\PermissionRegistry\Actions;

use App\Modules\PermissionRegistry\Models\GrantedPermission;
use Illuminate\Support\Collection;

class GetUserPermissionsAction
{
    /**
     * Получает список всех доступов пользователя
     */
    public function handle(int $userId, ?string $service = null): array
    {
        $query = GrantedPermission::where('user_id', $userId)
            ->where('enabled', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with(['permission', 'fieldValues.field']);

        if ($service) {
            $query->whereHas('permission', function ($q) use ($service) {
                $q->where('service', $service);
            });
        }

        $permissions = $query->get();

        return $this->formatPermissions($permissions);
    }

    /**
     * Форматирует доступы для удобного использования
     */
    private function formatPermissions(Collection $permissions): array
    {
        $result = [];

        foreach ($permissions as $grantedPermission) {
            $permission = $grantedPermission->permission;

            $permissionData = [
                'id' => $permission->id,
                'name' => $permission->name,
                'service' => $permission->service,
                'description' => $permission->description,
                'granted_at' => $grantedPermission->granted_at->toIso8601String(),
                'expires_at' => $grantedPermission->expires_at ? $grantedPermission->expires_at->toIso8601String() : null,
                'fields' => [],
            ];

            foreach ($grantedPermission->fieldValues as $fieldValue) {
                $permissionData['fields'][$fieldValue->field->name] = $fieldValue->value;
            }

            $result[] = $permissionData;
        }

        return $result;
    }
}
