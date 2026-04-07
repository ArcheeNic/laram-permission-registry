<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\GrantedPermissionStatus;
use ArcheeNic\PermissionRegistry\Exceptions\PermissionCannotBeDeletedException;
use ArcheeNic\PermissionRegistry\Models\Permission;

class DeletePermissionAction
{
    public function handle(Permission $permission): void
    {
        $activeGrantCount = $permission->grantedPermissions()
            ->whereNotIn('status', $this->terminalGrantStatuses())
            ->count();

        if ($activeGrantCount > 0) {
            throw PermissionCannotBeDeletedException::hasActiveGrants($activeGrantCount);
        }

        $dependentRows = $permission->dependents()
            ->with(['permission' => fn ($query) => $query->withTrashed()->select('id', 'name')])
            ->get()
            ->values();

        $dependentNames = $dependentRows
            ->pluck('permission.name')
            ->filter()
            ->unique()
            ->values();

        if ($dependentRows->isNotEmpty()) {
            if ($dependentNames->isEmpty()) {
                $dependentNames = $dependentRows
                    ->pluck('permission_id')
                    ->map(fn ($id) => 'ID: '.$id)
                    ->unique()
                    ->values();
            }

            throw PermissionCannotBeDeletedException::hasDependents($dependentNames);
        }

        $permission->delete();
    }

    private function terminalGrantStatuses(): array
    {
        return [
            GrantedPermissionStatus::REVOKED->value,
            GrantedPermissionStatus::REJECTED->value,
            GrantedPermissionStatus::FAILED->value,
        ];
    }
}
