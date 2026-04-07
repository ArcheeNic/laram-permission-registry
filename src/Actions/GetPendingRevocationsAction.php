<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\ManagementMode;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class GetPendingRevocationsAction
{
    public function handle(
        ?string $employeeCategory = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $perPage = in_array($perPage, [10, 15, 25, 50], true) ? $perPage : 15;

        return $this->buildBaseQuery($employeeCategory)
            ->with([
                'grantedPermissions' => fn ($query) => $query
                    ->where('enabled', true)
                    ->with(['permission', 'manualProvisionTasks']),
            ])
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }

    /**
     * @return array{users_count:int, permissions_count:int, automated_count:int, manual_count:int, declarative_count:int}
     */
    public function getSummary(?string $employeeCategory = null): array
    {
        $virtualUsersQuery = VirtualUser::query()
            ->where('status', VirtualUserStatus::DEACTIVATED->value)
            ->when($employeeCategory !== null && $employeeCategory !== '', function ($query) use ($employeeCategory) {
                $query->where('employee_category', $employeeCategory);
            })
            ->whereHas('grantedPermissions', function ($query) {
                $query->where('enabled', true);
            });

        $grantedPermissionsQuery = GrantedPermission::query()
            ->where('enabled', true)
            ->whereHas('user', function ($query) use ($employeeCategory) {
                $query->where('status', VirtualUserStatus::DEACTIVATED->value)
                    ->when($employeeCategory !== null && $employeeCategory !== '', function ($innerQuery) use ($employeeCategory) {
                        $innerQuery->where('employee_category', $employeeCategory);
                    });
            });

        return [
            'users_count' => (clone $virtualUsersQuery)->count(),
            'permissions_count' => (clone $grantedPermissionsQuery)->count(),
            'automated_count' => (clone $grantedPermissionsQuery)->whereHas('permission', function ($query) {
                $query->where('management_mode', ManagementMode::AUTOMATED->value)
                    ->orWhereNull('management_mode');
            })->count(),
            'manual_count' => (clone $grantedPermissionsQuery)->whereHas('permission', function ($query) {
                $query->where('management_mode', ManagementMode::MANUAL->value);
            })->count(),
            'declarative_count' => (clone $grantedPermissionsQuery)->whereHas('permission', function ($query) {
                $query->where('management_mode', ManagementMode::DECLARATIVE->value);
            })->count(),
        ];
    }

    private function buildBaseQuery(?string $employeeCategory = null): Builder
    {
        return VirtualUser::query()
            ->where('status', VirtualUserStatus::DEACTIVATED->value)
            ->when($employeeCategory !== null && $employeeCategory !== '', function ($query) use ($employeeCategory) {
                $query->where('employee_category', $employeeCategory);
            })
            ->whereHas('grantedPermissions', function ($query) {
                $query->where('enabled', true);
            })
            ->withCount([
                'grantedPermissions as pending_permissions_count' => fn ($query) => $query->where('enabled', true),
                'grantedPermissions as pending_automated_count' => function ($query) {
                    $query->where('enabled', true)
                        ->whereHas('permission', function ($permissionQuery) {
                            $permissionQuery->where('management_mode', ManagementMode::AUTOMATED->value)
                                ->orWhereNull('management_mode');
                        });
                },
                'grantedPermissions as pending_manual_count' => fn ($query) => $query
                    ->where('enabled', true)
                    ->whereHas('permission', fn ($permissionQuery) => $permissionQuery
                        ->where('management_mode', ManagementMode::MANUAL->value)),
                'grantedPermissions as pending_declarative_count' => fn ($query) => $query
                    ->where('enabled', true)
                    ->whereHas('permission', fn ($permissionQuery) => $permissionQuery
                        ->where('management_mode', ManagementMode::DECLARATIVE->value)),
            ]);
    }
}

