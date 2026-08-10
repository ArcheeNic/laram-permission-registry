<?php

namespace ArcheeNic\PermissionRegistry\Livewire\Concerns;

use ArcheeNic\PermissionRegistry\Enums\PermissionScope;
use ArcheeNic\PermissionRegistry\Jobs\GrantMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Jobs\RevokeMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionResource;
use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Services\PermissionDependencyResolver;
use Illuminate\Support\Collection;

trait ManagesPermissions
{
    public $permissionSearch = '';

    public $selectedPermissions = [];

    public $permissionFields = [];

    /** @var array<int, array<int, int>> permission_id => list<resource_id> */
    public array $permissionResources = [];

    public $expandedPermissionFields = [];

    public $expandedDependentPermissionFields = [];

    public $dependentPermissionFields = [];

    public $dependentSelectedPermissions = [];

    public array $dependentPermissionErrors = [];

    public function togglePermissionFields($permissionId)
    {
        if (isset($this->expandedPermissionFields[$permissionId])) {
            unset($this->expandedPermissionFields[$permissionId]);
        } else {
            $this->expandedPermissionFields[$permissionId] = true;
        }
    }

    public function toggleDependentPermissionFields($permissionId)
    {
        if (isset($this->expandedDependentPermissionFields[$permissionId])) {
            unset($this->expandedDependentPermissionFields[$permissionId]);
        } else {
            $this->expandedDependentPermissionFields[$permissionId] = true;
        }
    }

    protected function initializeResourcePermissionContainers(): void
    {
        $resourceScopedIds = Permission::query()
            ->where('scope', PermissionScope::Resource)
            ->pluck('id');

        foreach ($resourceScopedIds as $permId) {
            if (! array_key_exists($permId, $this->permissionResources) || ! is_array($this->permissionResources[$permId])) {
                $this->permissionResources[$permId] = [];
            }
        }
    }

    public function getAvailablePermissionsProperty()
    {
        if (! $this->selectedUserId) {
            return collect();
        }

        $dependentPermissionIds = $this->dependentPermissions->pluck('id')->toArray();

        return Permission::with('fields')
            ->select(['*'])
            ->whereNotIn('id', $dependentPermissionIds)
            ->when($this->permissionSearch, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->permissionSearch}%")
                        ->orWhere('service', 'like', "%{$this->permissionSearch}%")
                        ->orWhere('description', 'like', "%{$this->permissionSearch}%");
                });
            })
            ->orderBy('service')
            ->orderBy('name')
            ->get();
    }

    public function getPermissionResourceCatalogProperty(): array
    {
        $available = $this->availablePermissions;
        $byKind = [];

        foreach ($available as $permission) {
            $scope = $permission->scope ?? PermissionScope::Service;
            if ($scope !== PermissionScope::Resource || ! $permission->resource_kind) {
                continue;
            }
            $byKind[$permission->service.'|'.$permission->resource_kind] = true;
        }

        if ($byKind === []) {
            return [];
        }

        $catalog = [];
        foreach (array_keys($byKind) as $sk) {
            [$service, $kind] = explode('|', $sk);
            $resources = PermissionResource::query()
                ->where('service', $service)
                ->where('kind', $kind)
                ->where('present_in_source', true)
                ->orderBy('name')
                ->get(['id', 'service', 'kind', 'external_id', 'name']);

            foreach ($available as $permission) {
                if (($permission->scope ?? PermissionScope::Service) === PermissionScope::Resource
                    && $permission->service === $service
                    && $permission->resource_kind === $kind
                ) {
                    $catalog[$permission->id] = $resources;
                }
            }
        }

        return $catalog;
    }

    public function getDependentPermissionsProperty()
    {
        if (! $this->selectedUserId) {
            return collect();
        }

        $user = $this->loadDependentPermissionsSourceUser();
        if (! $user) {
            return collect();
        }

        $result = collect();

        $userGrantedPermissions = GrantedPermission::where('virtual_user_id', $this->selectedUserId)
            ->whereNull('resource_id')
            ->get()
            ->keyBy('permission_id');

        $this->collectPositionPermissions($result, $userGrantedPermissions, $user);
        $this->collectGroupPermissions($result, $userGrantedPermissions, $user);

        return $result->unique('id')->values();
    }

    protected function loadDependentPermissionsSourceUser(): ?VirtualUser
    {
        return VirtualUser::with([
            'positions.permissions',
            'positions.groups.permissions',
            'positions.parent.permissions',
            'positions.parent.groups.permissions',
            'positions.parent.parent.permissions',
            'positions.parent.parent.groups.permissions',
            'groups.permissions',
        ])->find($this->selectedUserId);
    }

    public function saveUserPermissions()
    {
        $this->clearFlashMessages();
        $this->dependentPermissionErrors = [];

        if (! $this->selectedUserId) {
            return;
        }

        $availablePermissionIds = $this->availablePermissions->pluck('id')->toArray();
        $userPermissions = GrantedPermission::where('virtual_user_id', $this->selectedUserId)->get();

        $this->dispatchDirectPermissionGrants($availablePermissionIds, $userPermissions);
        $this->dispatchDirectPermissionRevokes($availablePermissionIds, $userPermissions);

        $hasDependentErrors = $this->dispatchDependentPermissionGrants($userPermissions);
        $this->dispatchDependentPermissionRevokes($userPermissions);
        $this->dirtyDependentPermissionSelections = [];

        $this->initializeProcessingTracking();

        if (! $this->isProcessing && ! $hasDependentErrors) {
            $this->setFlashMessage(__('permission-registry::Permissions updated successfully'));
        }
    }

    protected function getFieldsForPermission($permission): array
    {
        $fields = [];

        $grantedPermission = GrantedPermission::where('virtual_user_id', $this->selectedUserId)
            ->where('permission_id', $permission->id)
            ->with('fieldValues.field')
            ->first();

        foreach ($permission->fields as $field) {
            $value = '';

            if ($field->is_global && isset($this->globalFields[$field->id])) {
                $value = $this->globalFields[$field->id];
                $this->dependentPermissionFields[$permission->id][$field->id] = $value;
            } elseif ($grantedPermission) {
                $fieldValue = $grantedPermission->fieldValues->first(fn ($item) => $item->permission_field_id == $field->id);
                if ($fieldValue) {
                    $value = $fieldValue->value;
                    $this->dependentPermissionFields[$permission->id][$field->id] = $value;
                }
            }

            $fields[] = [
                'id' => $field->id,
                'name' => $field->name,
                'default_value' => $field->default_value,
                'value' => $value,
                'is_global' => $field->is_global,
            ];
        }

        return $fields;
    }

    private function collectPositionPermissions($result, $userGrantedPermissions, VirtualUser $user): void
    {
        foreach ($user->positions as $position) {
            $positionsHierarchy = $this->getAllPositionsInHierarchy($position);

            foreach ($positionsHierarchy as $hierarchyPosition) {
                $sourceName = $hierarchyPosition->id === $position->id
                    ? $position->name
                    : $position->name.' → '.$hierarchyPosition->name;

                foreach ($hierarchyPosition->permissions as $permission) {
                    $result->push($this->buildPermissionData(
                        $permission, $userGrantedPermissions, 'position', $hierarchyPosition->id, $sourceName
                    ));
                }

                foreach ($hierarchyPosition->groups as $group) {
                    foreach ($group->permissions as $permission) {
                        $result->push($this->buildPermissionData(
                            $permission, $userGrantedPermissions, 'position_group', $group->id,
                            $sourceName.' ('.$group->name.')'
                        ));
                    }
                }
            }
        }
    }

    private function collectGroupPermissions($result, $userGrantedPermissions, VirtualUser $user): void
    {
        foreach ($user->groups as $group) {
            foreach ($group->permissions as $permission) {
                $result->push($this->buildPermissionData(
                    $permission, $userGrantedPermissions, 'group', $group->id, $group->name
                ));
            }
        }
    }

    private function buildPermissionData($permission, $userGrantedPermissions, string $sourceType, int $sourceId, string $sourceName): array
    {
        $fields = $this->getFieldsForPermission($permission);
        $grantedPermission = $userGrantedPermissions->get($permission->id);

        return [
            'id' => $permission->id,
            'name' => $permission->name,
            'service' => $permission->service,
            'description' => $permission->description,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_name' => $sourceName,
            'has_fields' => count($fields) > 0,
            'fields' => $fields,
            'status' => $grantedPermission ? ($grantedPermission->status ?? 'granted') : null,
            'status_message' => $grantedPermission?->status_message,
        ];
    }

    private function getAllPositionsInHierarchy(Position $position): Collection
    {
        $result = collect([$position]);
        $visited = [$position->id];
        $current = $position;

        while ($current->parent_id) {
            if (in_array($current->parent_id, $visited)) {
                break;
            }

            if (! $current->relationLoaded('parent')) {
                $current->load(['parent.permissions', 'parent.groups.permissions']);
            }

            if (! $current->parent) {
                break;
            }

            $current = $current->parent;
            $visited[] = $current->id;
            $result->push($current);
        }

        return $result;
    }

    private function dispatchDirectPermissionGrants(array $availablePermissionIds, $userPermissions): void
    {
        $permissionsById = $this->availablePermissions->keyBy('id');
        $permissionsToGrant = [];

        foreach ($availablePermissionIds as $permId) {
            $permission = $permissionsById->get($permId);
            if (! $permission) {
                continue;
            }

            $scope = $permission->scope ?? PermissionScope::Service;
            $isSelected = isset($this->selectedPermissions[$permId]) && $this->selectedPermissions[$permId];

            if ($scope === PermissionScope::Resource) {
                if (! $isSelected) {
                    continue;
                }
                $selectedResourceIds = array_values(array_filter(array_map(
                    static fn ($v) => (int) $v,
                    $this->permissionResources[$permId] ?? []
                )));
                $existing = $userPermissions->where('permission_id', $permId)
                    ->where('enabled', true)
                    ->reject(fn ($g) => $g->hasErrorStatus());
                $existingResourceIds = $existing->pluck('resource_id')->filter()->values()->all();

                $toGrant = array_diff($selectedResourceIds, $existingResourceIds);
                foreach ($toGrant as $resourceId) {
                    $permissionsToGrant[] = [
                        'permissionId' => $permId,
                        'resourceId' => $resourceId,
                        'fieldValues' => $this->permissionFields[$permId] ?? [],
                        'meta' => [],
                        'expiresAt' => null,
                    ];
                }
            } else {
                $existingPermission = $userPermissions->firstWhere(fn ($g) => $g->permission_id === $permId && $g->resource_id === null);
                if ($isSelected && (! $existingPermission || ! $existingPermission->enabled || $existingPermission->hasErrorStatus())) {
                    $permissionsToGrant[] = [
                        'permissionId' => $permId,
                        'resourceId' => null,
                        'fieldValues' => $this->permissionFields[$permId] ?? [],
                        'meta' => $existingPermission ? $existingPermission->meta : [],
                        'expiresAt' => $existingPermission ? $existingPermission->expires_at : null,
                    ];
                }
            }
        }

        if (! empty($permissionsToGrant)) {
            GrantMultiplePermissionsJob::dispatch($this->selectedUserId, $permissionsToGrant);
        }
    }

    private function dispatchDirectPermissionRevokes(array $availablePermissionIds, $userPermissions): void
    {
        $permissionsById = $this->availablePermissions->keyBy('id');
        $permissionsToRevoke = [];

        foreach ($availablePermissionIds as $permId) {
            $permission = $permissionsById->get($permId);
            if (! $permission) {
                continue;
            }

            $scope = $permission->scope ?? PermissionScope::Service;
            $isSelected = isset($this->selectedPermissions[$permId]) && $this->selectedPermissions[$permId];

            if ($scope === PermissionScope::Resource) {
                $existing = $userPermissions->where('permission_id', $permId)->where('enabled', true);
                if (! $isSelected) {
                    foreach ($existing as $granted) {
                        $permissionsToRevoke[] = [
                            'permissionId' => $permId,
                            'resourceId' => $granted->resource_id,
                        ];
                    }

                    continue;
                }
                $selectedResourceIds = array_values(array_filter(array_map(
                    static fn ($v) => (int) $v,
                    $this->permissionResources[$permId] ?? []
                )));
                foreach ($existing as $granted) {
                    if ($granted->resource_id !== null && ! in_array($granted->resource_id, $selectedResourceIds, true)) {
                        $permissionsToRevoke[] = [
                            'permissionId' => $permId,
                            'resourceId' => $granted->resource_id,
                        ];
                    }
                }
            } else {
                $existingPermission = $userPermissions->firstWhere(fn ($g) => $g->permission_id === $permId && $g->resource_id === null);
                if (! $isSelected && $existingPermission && $existingPermission->enabled) {
                    $permissionsToRevoke[] = [
                        'permissionId' => $permId,
                        'resourceId' => null,
                    ];
                }
            }
        }

        if (! empty($permissionsToRevoke)) {
            RevokeMultiplePermissionsJob::dispatch($this->selectedUserId, $permissionsToRevoke);
        }
    }

    private function dispatchDependentPermissionGrants($userPermissions): bool
    {
        $dependentPermissionsToGrant = [];
        foreach ($this->dependentSelectedPermissions as $permId => $isEnabled) {
            $existingPermission = $userPermissions->first(fn ($g) => $g->permission_id === $permId && $g->resource_id === null);

            if ($isEnabled && (! $existingPermission || ! $existingPermission->enabled || $existingPermission->hasErrorStatus())) {
                $dependentPermissionsToGrant[] = [
                    'permissionId' => $permId,
                    'resourceId' => null,
                    'fieldValues' => $this->dependentPermissionFields[$permId] ?? [],
                    'meta' => $existingPermission ? $existingPermission->meta : [],
                    'expiresAt' => $existingPermission ? $existingPermission->expires_at : null,
                ];
            }
        }

        $hasDependentErrors = $this->validateDependentPermissions($dependentPermissionsToGrant);

        if (! empty($dependentPermissionsToGrant) && ! $hasDependentErrors) {
            GrantMultiplePermissionsJob::dispatch($this->selectedUserId, $dependentPermissionsToGrant);
        } elseif ($hasDependentErrors) {
            $this->setFlashError(__('permission-registry::Fix dependent permission errors before saving'));
        }

        return $hasDependentErrors;
    }

    private function validateDependentPermissions(array $dependentPermissionsToGrant): bool
    {
        $dependencyResolver = app(PermissionDependencyResolver::class);
        $hasDependentErrors = false;

        foreach ($dependentPermissionsToGrant as $item) {
            $permId = $item['permissionId'];
            $permission = Permission::with('fields')->find($permId);
            if (! $permission) {
                continue;
            }

            $depResult = $dependencyResolver->validatePermissionDependencies($this->selectedUserId, $permission, 'grant');
            if (! $depResult->isValid) {
                $this->dependentPermissionErrors[$permId] = [
                    'message' => $depResult->getErrorMessage(),
                    'missing_fields' => [],
                    'missing_permissions' => $depResult->missingPermissions,
                ];
                $hasDependentErrors = true;

                continue;
            }

            $fieldValuesByFieldId = $item['fieldValues'] ?? [];
            $fieldsResult = $dependencyResolver->validateGlobalFieldsWithValues(
                $this->selectedUserId, $permission, $fieldValuesByFieldId
            );
            if (! $fieldsResult->isValid) {
                $this->dependentPermissionErrors[$permId] = [
                    'message' => $fieldsResult->getErrorMessage(),
                    'missing_fields' => $fieldsResult->missingFields,
                    'missing_permissions' => [],
                ];
                $hasDependentErrors = true;
            }
        }

        return $hasDependentErrors;
    }

    private function dispatchDependentPermissionRevokes($userPermissions): void
    {
        $dependentPermissionsToRevoke = [];
        foreach ($this->dependentSelectedPermissions as $permId => $isEnabled) {
            $existingPermission = $userPermissions->first(fn ($g) => $g->permission_id === $permId && $g->resource_id === null);

            if (! $isEnabled && $existingPermission && $existingPermission->enabled) {
                $dependentPermissionsToRevoke[] = [
                    'permissionId' => $permId,
                    'resourceId' => null,
                ];
            }
        }

        if (! empty($dependentPermissionsToRevoke)) {
            RevokeMultiplePermissionsJob::dispatch($this->selectedUserId, $dependentPermissionsToRevoke);
        }
    }
}
