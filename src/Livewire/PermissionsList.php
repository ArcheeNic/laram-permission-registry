<?php

namespace ArcheeNic\PermissionRegistry\Livewire;

use ArcheeNic\PermissionRegistry\Models\Permission;
use Livewire\Component;
use Livewire\WithPagination;

class PermissionsList extends Component
{
    use WithPagination;

    public $search = '';
    public $service = '';
    public $perPage = 15;

    protected $queryString = [
        'search' => ['except' => ''],
        'service' => ['except' => ''],
        'perPage' => ['except' => 15],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
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
            ->with(['fields', 'groups'])
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('permission-registry::livewire.permissions-list', [
            'permissions' => $this->permissions,
            'services' => Permission::select('service')->distinct()->pluck('service'),
        ]);
    }
}
