<?php

namespace ArcheeNic\PermissionRegistry\Livewire;

use ArcheeNic\PermissionRegistry\Actions\DeletePermissionAction;
use ArcheeNic\PermissionRegistry\Enums\ManagementMode;
use ArcheeNic\PermissionRegistry\Enums\RiskLevel;
use ArcheeNic\PermissionRegistry\Exceptions\PermissionCannotBeDeletedException;
use ArcheeNic\PermissionRegistry\Models\Permission;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class PermissionsList extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $search = '';
    public $service = '';
    public $managementMode = '';
    public $riskLevel = '';
    public $perPage = 15;
    public $confirmingDelete = false;
    public $permissionToDelete = null;
    public $deleteError = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'service' => ['except' => ''],
        'managementMode' => ['except' => ''],
        'riskLevel' => ['except' => ''],
        'perPage' => ['except' => 15],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingManagementMode()
    {
        $this->resetPage();
    }

    public function updatingRiskLevel()
    {
        $this->resetPage();
    }

    public function updatingPerPage($value): void
    {
        if (!in_array((int) $value, [10, 15, 25, 50], true)) {
            $this->perPage = 15;
        }

        $this->resetPage();
    }

    public function confirmDelete(int $permissionId): void
    {
        $this->authorize('permission-registry.manage');

        $this->confirmingDelete = true;
        $this->permissionToDelete = $permissionId;
        $this->deleteError = null;
    }

    public function deletePermission(DeletePermissionAction $action): void
    {
        $this->authorize('permission-registry.manage');

        if (!$this->permissionToDelete) {
            return;
        }

        $permission = Permission::query()->find($this->permissionToDelete);

        if (!$permission) {
            $this->deleteError = __('permission-registry::Cannot delete permission');
            return;
        }

        try {
            $action->handle($permission);
            $this->cancelDelete();
            session()->flash('success', __('permission-registry::Permission deleted successfully'));
        } catch (PermissionCannotBeDeletedException $exception) {
            $this->deleteError = $exception->getUserMessage();
        }
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = false;
        $this->permissionToDelete = null;
        $this->deleteError = null;
    }

    public function getPermissionsProperty()
    {
        return Permission::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                });
            })
            ->when($this->service, function ($query) {
                $query->where('service', $this->service);
            })
            ->when($this->managementMode, function ($query) {
                $query->where('management_mode', $this->managementMode);
            })
            ->when($this->riskLevel, function ($query) {
                $query->where('risk_level', $this->riskLevel);
            })
            ->with(['fields', 'groups', 'systemOwner'])
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('permission-registry::livewire.permissions-list', [
            'permissions' => $this->permissions,
            'services' => Permission::select('service')->distinct()->pluck('service'),
            'managementModes' => ManagementMode::cases(),
            'riskLevels' => RiskLevel::cases(),
        ]);
    }
}
