<?php

namespace ArcheeNic\PermissionRegistry\Livewire\Concerns;

use ArcheeNic\PermissionRegistry\Jobs\GrantMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Jobs\RevokeMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Services\PermissionDependencyResolver;

trait ManagesPermissions
{
    public $permissionSearch = '';
    public $selectedPermissions = [];
    public $permissionFields = [];
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

    public function getAvailablePermissionsProperty()
    {
        if (!$this->selectedUserId) {
            return collect();
        }

        $dependentPermissionIds = $this->dependentPermissions->pluck('id')->toArray();

        return Permission::with('fields')
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

    public function getDependentPermissionsProperty()
    {
        if (!$this->selectedUserId) {
            return collect();
        }

        $result = collect();

        $userGrantedPermissions = GrantedPermission::where('virtual_user_id', $this->selectedUserId)
            ->get()
            ->keyBy('permission_id');

        $this->collectPositionPermissions($result, $userGrantedPermissions);
        $this->collectGroupPermissions($result, $userGrantedPermissions);

        return $result->unique('id')->values();
    }

    public function saveUserPermissions()
    {
        $this->clearFlashMessages();
        $this->dependentPermissionErrors = [];

        if (!$this->selectedUserId) {
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

        if (!$this->isProcessing && !$hasDependentErrors) {
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

    private function collectPositionPermissions($result, $userGrantedPermissions): void
    {
        foreach ($this->selectedUser->positions as $position) {
            $positionsHierarchy = $this->getAllPositionsInHierarchy($position);

            foreach ($positionsHierarchy as $hierarchyPosition) {
                $sourceName = $hierarchyPosition->id === $position->id
                    ? $position->name
                    : $position->name . ' → ' . $hierarchyPosition->name;

                foreach ($hierarchyPosition->permissions as $permission) {
                    $result->push($this->buildPermissionData(
                        $permission, $userGrantedPermissions, 'position', $hierarchyPosition->id, $sourceName
                    ));
                }

                foreach ($hierarchyPosition->groups as $group) {
                    foreach ($group->permissions as $permission) {
                        $result->push($this->buildPermissionData(
                            $permission, $userGrantedPermissions, 'position_group', $group->id,
                            $sourceName . ' (' . $group->name . ')'
                        ));
                    }
                }
            }
        }
    }

    private function collectGroupPermissions($result, $userGrantedPermissions): void
    {
        foreach ($this->selectedUser->groups as $group) {
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

    private function getAllPositionsInHierarchy(Position $position): \Illuminate\Support\Collection
    {
        $result = collect([$position]);
        $visited = [$position->id];
        $current = $position;

        while ($current->parent_id) {
            if (in_array($current->parent_id, $visited)) {
                break;
            }

            if (!$current->relationLoaded('parent')) {
                $current->load(['parent.permissions', 'parent.groups.permissions']);
            }

            if (!$current->parent) {
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
        $permissionsToGrant = [];
        foreach ($availablePermissionIds as $permId) {
            $isSelected = isset($this->selectedPermissions[$permId]) && $this->selectedPermissions[$permId];
            $existingPermission = $userPermissions->firstWhere('permission_id', $permId);

            if ($isSelected && (!$existingPermission || !$existingPermission->enabled)) {
                $permissionsToGrant[] = [
                    'permissionId' => $permId,
                    'fieldValues' => $this->permissionFields[$permId] ?? [],
                    'meta' => $existingPermission ? $existingPermission->meta : [],
                    'expiresAt' => $existingPermission ? $existingPermission->expires_at : null,
                ];
            }
        }

        if (!empty($permissionsToGrant)) {
            GrantMultiplePermissionsJob::dispatch($this->selectedUserId, $permissionsToGrant);
        }
    }

    private function dispatchDirectPermissionRevokes(array $availablePermissionIds, $userPermissions): void
    {
        $permissionsToRevoke = [];
        foreach ($availablePermissionIds as $permId) {
            $isSelected = isset($this->selectedPermissions[$permId]) && $this->selectedPermissions[$permId];
            $existingPermission = $userPermissions->firstWhere('permission_id', $permId);

            if (!$isSelected && $existingPermission && $existingPermission->enabled) {
                $permissionsToRevoke[] = $permId;
            }
        }

        if (!empty($permissionsToRevoke)) {
            RevokeMultiplePermissionsJob::dispatch($this->selectedUserId, $permissionsToRevoke);
        }
    }

    private function dispatchDependentPermissionGrants($userPermissions): bool
    {
        $dependentPermissionsToGrant = [];
        foreach ($this->dependentSelectedPermissions as $permId => $isEnabled) {
            $existingPermission = $userPermissions->firstWhere('permission_id', $permId);

            if ($isEnabled && (!$existingPermission || !$existingPermission->enabled)) {
                $dependentPermissionsToGrant[] = [
                    'permissionId' => $permId,
                    'fieldValues' => $this->dependentPermissionFields[$permId] ?? [],
                    'meta' => $existingPermission ? $existingPermission->meta : [],
                    'expiresAt' => $existingPermission ? $existingPermission->expires_at : null,
                ];
            }
        }

        $hasDependentErrors = $this->validateDependentPermissions($dependentPermissionsToGrant);

        if (!empty($dependentPermissionsToGrant) && !$hasDependentErrors) {
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
            if (!$permission) {
                continue;
            }

            $depResult = $dependencyResolver->validatePermissionDependencies($this->selectedUserId, $permission, 'grant');
            if (!$depResult->isValid) {
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
            if (!$fieldsResult->isValid) {
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
            $existingPermission = $userPermissions->firstWhere('permission_id', $permId);

            if (!$isEnabled && $existingPermission && $existingPermission->enabled) {
                $dependentPermissionsToRevoke[] = $permId;
            }
        }

        if (!empty($dependentPermissionsToRevoke)) {
            RevokeMultiplePermissionsJob::dispatch($this->selectedUserId, $dependentPermissionsToRevoke);
        }
    }
}
