<?php

namespace ArcheeNic\PermissionRegistry\Livewire;

use App\Models\User;
use ArcheeNic\PermissionRegistry\Actions\GrantPermissionAction;
use ArcheeNic\PermissionRegistry\Actions\RevokePermissionAction;
use ArcheeNic\PermissionRegistry\Actions\GetVirtualUserFieldValueAction;
use ArcheeNic\PermissionRegistry\Actions\UpdateVirtualUserGlobalFieldsAction;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;

class UserPermissions extends Component
{
    use WithPagination;

    public $userId;
    public $search = '';
    public $selectedPermission = null;
    public $fieldValues = [];
    public $meta = [];
    public $expiresAt = null;
    public $showGlobalFieldsModal = false;
    public $missingGlobalFields = [];

    protected $listeners = ['refreshUserPermissions' => '$refresh'];

    public function mount($userId)
    {
        $this->userId = $userId;
    }

    public function selectPermission($permissionId)
    {
        $this->selectedPermission = Permission::with('fields')->find($permissionId);

        // Заполнение значений полей по умолчанию или существующих глобальных значений
        $this->fieldValues = [];
        foreach ($this->selectedPermission->fields as $field) {
            if ($field->is_global) {
                // Для глобальных полей берем существующее значение из virtual_user_field_values
                $existingValue = VirtualUserFieldValue::where('virtual_user_id', $this->userId)
                    ->where('permission_field_id', $field->id)
                    ->value('value');
                $this->fieldValues[$field->id] = $existingValue ?? $field->default_value;
            } else {
                $this->fieldValues[$field->id] = $field->default_value;
            }
        }
    }

    public function grantPermission()
    {
        $this->validate([
            'selectedPermission' => 'required',
        ]);

        try {
            $action = app(GrantPermissionAction::class);
            $action->handle(
                $this->userId,
                $this->selectedPermission->id,
                $this->fieldValues,
                $this->meta,
                $this->expiresAt
            );

            $this->reset(['selectedPermission', 'fieldValues', 'meta', 'expiresAt']);
            $this->dispatch('refreshUserPermissions');
            session()->flash('success', 'Право успешно выдано');
        } catch (ValidationException $e) {
            // Если не заполнены обязательные глобальные поля
            if (isset($e->errors()['missing_fields'])) {
                $this->missingGlobalFields = $e->errors()['missing_fields'];
                $this->showGlobalFieldsModal = true;
            } else {
                throw $e;
            }
        }
    }

    public function saveGlobalFieldsAndGrant()
    {
        // Сохранить глобальные поля
        $updateAction = app(UpdateVirtualUserGlobalFieldsAction::class);
        
        $globalFieldsToSave = [];
        foreach ($this->missingGlobalFields as $field) {
            if (!empty($this->fieldValues[$field['id']])) {
                $globalFieldsToSave[$field['id']] = $this->fieldValues[$field['id']];
            }
        }

        if (!empty($globalFieldsToSave)) {
            $updateAction->execute($this->userId, $globalFieldsToSave);
        }

        // Закрыть модальное окно
        $this->showGlobalFieldsModal = false;
        $this->missingGlobalFields = [];

        // Повторить попытку выдачи права
        $this->grantPermission();
    }

    public function closeGlobalFieldsModal()
    {
        $this->showGlobalFieldsModal = false;
        $this->missingGlobalFields = [];
    }

    public function revokePermission($grantedPermissionId)
    {
        $grantedPermission = GrantedPermission::find($grantedPermissionId);

        if ($grantedPermission) {
            $action = app(RevokePermissionAction::class);
            $action->handle($this->userId, $grantedPermission->permission_id);
            $this->dispatch('refreshUserPermissions');
        }
    }

    public function getUserProperty()
    {
        return VirtualUser::find($this->userId);
    }

    public function getGrantedPermissionsProperty()
    {
        return GrantedPermission::where('virtual_user_id', $this->userId)
            ->with(['permission', 'fieldValues.field'])
            ->get();
    }

    public function getAvailablePermissionsProperty()
    {
        $grantedPermissionIds = $this->grantedPermissions->pluck('permission_id');

        return Permission::whereNotIn('id', $grantedPermissionIds)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('service', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                });
            })
            ->get();
    }

    public function render()
    {
        return view('permission-registry::livewire.user-permissions');
    }
}
