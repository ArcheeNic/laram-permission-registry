<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\GrantedPermissionStatus;
use ArcheeNic\PermissionRegistry\Exceptions\PermissionCannotBeDeletedException;
use ArcheeNic\PermissionRegistry\Models\Permission;

class DeletePermissionAction
{
    public function __construct(private RevokePermissionAction $revokeAction) {}

    public function handle(Permission $permission, bool $invokeTriggers = true): void
    {
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

        $permission->grantedPermissions()
            ->whereNotIn('status', $this->terminalGrantStatuses())
            ->get()
            ->each(function ($granted) use ($invokeTriggers) {
                $this->revokeAction->handle(
                    userId: $granted->virtual_user_id,
                    permissionId: $granted->permission_id,
                    skipTriggers: ! $invokeTriggers,
                    executeTriggersSync: $invokeTriggers,
                    resourceId: $granted->resource_id,
                );
            });

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
