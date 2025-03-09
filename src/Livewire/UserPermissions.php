<?php

namespace ArcheeNic\PermissionRegistry\Livewire;

use App\Models\User;
use ArcheeNic\PermissionRegistry\Actions\GrantPermissionAction;
use ArcheeNic\PermissionRegistry\Actions\RevokePermissionAction;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
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

    protected $listeners = ['refreshUserPermissions' => '$refresh'];

    public function mount($userId)
    {
        $this->userId = $userId;
    }

    public function selectPermission($permissionId)
    {
        $this->selectedPermission = Permission::with('fields')->find($permissionId);

        // Заполнение значений полей по умолчанию
        $this->fieldValues = [];
        foreach ($this->selectedPermission->fields as $field) {
            $this->fieldValues[$field->id] = $field->default_value;
        }
    }

    public function grantPermission()
    {
        $this->validate([
            'selectedPermission' => 'required',
        ]);

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
        return GrantedPermission::where('user_id', $this->userId)
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
