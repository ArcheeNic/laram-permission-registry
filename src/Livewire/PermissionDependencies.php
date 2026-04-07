<?php

namespace ArcheeNic\PermissionRegistry\Livewire;

use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionDependency;
use Livewire\Component;

class PermissionDependencies extends Component
{
    public Permission $permission;
    public string $activeTab = 'grant';
    public ?int $selectedPermissionId = null;
    public bool $isStrict = false;
    public ?string $flashMessage = null;
    public ?string $flashError = null;

    public function mount(Permission $permission)
    {
        $this->permission = $permission;
    }

    public function setActiveTab(string $tab)
    {
        $this->activeTab = $tab;
        $this->resetForm();
    }

    public function addDependency()
    {
        $this->clearFlashMessages();

        if (!$this->selectedPermissionId) {
            $this->flashError = __('permission-registry::Please select a permission');
            return;
        }

        // Проверка на циклическую зависимость
        if ($this->wouldCreateCircularDependency($this->permission->id, $this->selectedPermissionId, $this->activeTab)) {
            $this->flashError = __('permission-registry::Circular dependency is not allowed');
            return;
        }

        // Проверка на дублирование
        $exists = PermissionDependency::where('permission_id', $this->permission->id)
            ->where('required_permission_id', $this->selectedPermissionId)
            ->where('event_type', $this->activeTab)
            ->exists();

        if ($exists) {
            $this->flashError = __('permission-registry::This dependency already exists');
            return;
        }

        PermissionDependency::create([
            'permission_id' => $this->permission->id,
            'required_permission_id' => $this->selectedPermissionId,
            'is_strict' => $this->isStrict,
            'event_type' => $this->activeTab,
        ]);

        $this->flashMessage = __('permission-registry::Dependency added successfully');
        $this->resetForm();
    }

    public function toggleStrict(int $dependencyId)
    {
        $dependency = PermissionDependency::find($dependencyId);
        if ($dependency && $dependency->permission_id === $this->permission->id) {
            $dependency->update(['is_strict' => !$dependency->is_strict]);
        }
    }

    public function removeDependency(int $dependencyId)
    {
        $this->clearFlashMessages();
        
        $dependency = PermissionDependency::find($dependencyId);
        if ($dependency && $dependency->permission_id === $this->permission->id) {
            $dependency->delete();
            $this->flashMessage = __('permission-registry::Dependency removed successfully');
        }
    }

    public function getGrantDependenciesProperty()
    {
        return $this->permission->grantDependencies()
            ->with('requiredPermission')
            ->get();
    }

    public function getRevokeDependenciesProperty()
    {
        return $this->permission->revokeDependencies()
            ->with('requiredPermission')
            ->get();
    }

    public function getAvailablePermissionsProperty()
    {
        $existingIds = $this->activeTab === 'grant'
            ? $this->grantDependencies->pluck('required_permission_id')->toArray()
            : $this->revokeDependencies->pluck('required_permission_id')->toArray();

        return Permission::where('id', '!=', $this->permission->id)
            ->whereNotIn('id', $existingIds)
            ->orderBy('service')
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('permission-registry::livewire.permission-dependencies');
    }

    private function resetForm(): void
    {
        $this->selectedPermissionId = null;
        $this->isStrict = false;
    }

    private function clearFlashMessages(): void
    {
        $this->flashMessage = null;
        $this->flashError = null;
    }

    private function wouldCreateCircularDependency(int $permissionId, int $requiredPermissionId, string $eventType): bool
    {
        // Простая проверка: если требуемое право зависит от текущего с тем же event_type
        $exists = PermissionDependency::where('permission_id', $requiredPermissionId)
            ->where('required_permission_id', $permissionId)
            ->where('event_type', $eventType)
            ->exists();

        return $exists;
    }
}
