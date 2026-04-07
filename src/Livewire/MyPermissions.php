<?php

namespace ArcheeNic\PermissionRegistry\Livewire;

use ArcheeNic\PermissionRegistry\Actions\GrantPermissionAction;
use ArcheeNic\PermissionRegistry\Contracts\UserToVirtualUserResolver;
use ArcheeNic\PermissionRegistry\Enums\GrantedPermissionStatus;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class MyPermissions extends Component
{
    public ?int $currentUserId = null;
    public ?int $virtualUserId = null;
    public string $search = '';
    public string $statusFilter = '';
    public bool $showRequestModal = false;
    public ?int $selectedPermissionId = null;
    public array $fieldValues = [];

    public ?string $flashMessage = null;
    public ?string $flashError = null;

    public function mount(?int $currentUserId = null): void
    {
        $this->currentUserId = $currentUserId;
        $resolver = app(UserToVirtualUserResolver::class);
        $this->virtualUserId = $resolver->resolve($this->currentUserId);
    }

    public function getPermissionsProperty()
    {
        if (!$this->virtualUserId) {
            return collect();
        }

        $query = GrantedPermission::with('permission')
            ->where('virtual_user_id', $this->virtualUserId);

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search) {
            $query->whereHas('permission', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('service', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function openRequestModal(): void
    {
        $this->showRequestModal = true;
        $this->selectedPermissionId = null;
        $this->fieldValues = [];
    }

    public function closeRequestModal(): void
    {
        $this->showRequestModal = false;
        $this->selectedPermissionId = null;
        $this->fieldValues = [];
    }

    public function selectPermission(int $permissionId): void
    {
        $this->selectedPermissionId = $permissionId;
        $permission = Permission::with('fields')->find($permissionId);
        if ($permission) {
            $this->fieldValues = [];
            foreach ($permission->fields as $field) {
                if (!$field->is_global) {
                    $this->fieldValues[$field->id] = $field->default_value ?? '';
                }
            }
        }
    }

    private const MAX_FIELD_VALUE_LENGTH = 1000;

    public function requestPermission(): void
    {
        if (!$this->virtualUserId || !$this->selectedPermissionId) {
            return;
        }

        $permission = Permission::find($this->selectedPermissionId);
        if (!$permission) {
            $this->flashError = __('permission-registry::messages.permission_not_found');
            return;
        }

        foreach ($this->fieldValues as $value) {
            if (is_string($value) && mb_strlen($value) > self::MAX_FIELD_VALUE_LENGTH) {
                $this->flashError = __('permission-registry::messages.field_value_too_long', [
                    'max' => self::MAX_FIELD_VALUE_LENGTH,
                ]);
                return;
            }
        }

        $existing = GrantedPermission::where('virtual_user_id', $this->virtualUserId)
            ->where('permission_id', $this->selectedPermissionId)
            ->whereNotIn('status', [
                GrantedPermissionStatus::REVOKED->value,
                GrantedPermissionStatus::REJECTED->value,
                GrantedPermissionStatus::FAILED->value,
            ])
            ->exists();

        if ($existing) {
            $this->flashError = __('permission-registry::messages.permission_already_requested');
            return;
        }

        try {
            $grantAction = app(GrantPermissionAction::class);
            $grantAction->handle(
                userId: $this->virtualUserId,
                permissionId: $this->selectedPermissionId,
                fieldValues: $this->fieldValues,
                requestedBy: $this->virtualUserId,
                skipTriggers: false,
                executeTriggersSync: false
            );

            $this->closeRequestModal();
            $this->flashMessage = __('permission-registry::messages.permission_requested');
            $this->flashError = null;
        } catch (ValidationException $e) {
            $this->flashError = $e->validator->errors()->first();
        } catch (\Exception $e) {
            $this->flashError = __('permission-registry::messages.request_error');
        }
    }

    public function getAvailablePermissionsProperty()
    {
        if (!$this->virtualUserId) {
            return collect();
        }

        $grantedIds = GrantedPermission::where('virtual_user_id', $this->virtualUserId)
            ->whereNotIn('status', [
                GrantedPermissionStatus::REVOKED->value,
                GrantedPermissionStatus::REJECTED->value,
                GrantedPermissionStatus::FAILED->value,
            ])
            ->pluck('permission_id');

        return Permission::whereNotIn('id', $grantedIds)
            ->orderBy('service')
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('permission-registry::livewire.my-permissions');
    }
}
