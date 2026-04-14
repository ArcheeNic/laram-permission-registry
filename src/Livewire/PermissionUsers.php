<?php

namespace ArcheeNic\PermissionRegistry\Livewire;

use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use Livewire\Component;
use Livewire\WithPagination;

class PermissionUsers extends Component
{
    use WithPagination;

    public int $permissionId;
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $users = GrantedPermission::query()
            ->where('permission_id', $this->permissionId)
            ->with('user')
            ->when($this->search, function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', "%{$this->search}%");
                });
            })
            ->latest('granted_at')
            ->paginate(10);

        return view('permission-registry::livewire.permission-users', [
            'users' => $users,
        ]);
    }
}
