<?php

namespace ArcheeNic\PermissionRegistry\Livewire\Concerns;

use ArcheeNic\PermissionRegistry\Actions\UpdateVirtualUserGlobalFieldsAction;
use ArcheeNic\PermissionRegistry\Jobs\GrantMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;

trait ManagesManualGrant
{
    public bool $showManualGrantModal = false;
    public ?int $manualGrantPermissionId = null;
    public array $manualGrantMissingFields = [];
    public array $manualGrantFieldValues = [];

    public function openManualGrantModal(int $permissionId): void
    {
        $this->manualGrantPermissionId = $permissionId;
        $this->manualGrantFieldValues = [];

        $permission = Permission::with('fields')->find($permissionId);
        if (!$permission) {
            return;
        }

        $this->manualGrantMissingFields = [];
        foreach ($permission->fields as $field) {
            if ($field->is_global) {
                $currentValue = $this->globalFields[$field->id] ?? '';
                $this->manualGrantMissingFields[] = [
                    'id' => $field->id,
                    'name' => $field->name,
                    'value' => $currentValue,
                ];
                $this->manualGrantFieldValues[$field->id] = $currentValue;
            }
        }

        $this->showManualGrantModal = true;
    }

    public function closeManualGrantModal(): void
    {
        $this->showManualGrantModal = false;
        $this->manualGrantPermissionId = null;
        $this->manualGrantMissingFields = [];
        $this->manualGrantFieldValues = [];
    }

    public function saveGlobalFieldsAndRetryGrant(): void
    {
        $this->clearFlashMessages();

        if (!$this->selectedUserId || !$this->manualGrantPermissionId) {
            $this->closeManualGrantModal();
            return;
        }

        $fieldsToSave = [];
        foreach ($this->manualGrantFieldValues as $fieldId => $value) {
            if (!empty($value)) {
                $fieldsToSave[$fieldId] = $value;
                $this->globalFields[$fieldId] = $value;
            }
        }

        if (!empty($fieldsToSave)) {
            $updateAction = app(UpdateVirtualUserGlobalFieldsAction::class);
            $updateAction->execute($this->selectedUserId, $fieldsToSave);
        }

        GrantedPermission::where('virtual_user_id', $this->selectedUserId)
            ->where('permission_id', $this->manualGrantPermissionId)
            ->where('status', 'failed')
            ->delete();

        $permissionId = $this->manualGrantPermissionId;
        $fieldValues = $this->dependentPermissionFields[$permissionId] ?? [];

        foreach ($fieldsToSave as $fieldId => $value) {
            $fieldValues[$fieldId] = $value;
        }

        GrantMultiplePermissionsJob::dispatch($this->selectedUserId, [
            [
                'permissionId' => $permissionId,
                'fieldValues' => $fieldValues,
                'meta' => [],
                'expiresAt' => null,
            ]
        ]);

        $this->closeManualGrantModal();
        $this->setFlashMessage(__('permission-registry::Permission grant queued'));

        $this->checkPermissionStatus();
    }
}
