<?php

namespace ArcheeNic\PermissionRegistry\Livewire;

use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use Livewire\Component;
use Livewire\WithPagination;

class GroupsList extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 15;
    public $confirmingDelete = false;
    public $groupToDelete = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 15],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function confirmDelete($groupId)
    {
        $this->confirmingDelete = true;
        $this->groupToDelete = $groupId;
    }

    public function deleteGroup()
    {
        if ($this->groupToDelete) {
            $group = PermissionGroup::find($this->groupToDelete);
            if ($group) {
                $group->delete();
                session()->flash('success', __('permission-registry::Group deleted successfully'));
            }
        }

        $this->confirmingDelete = false;
        $this->groupToDelete = null;
    }

    public function cancelDelete()
    {
        $this->confirmingDelete = false;
        $this->groupToDelete = null;
    }

    public function render()
    {
        $groups = PermissionGroup::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                });
            })
            ->withCount('permissions')
            ->paginate($this->perPage);

        return view('permission-registry::livewire.groups-list', compact('groups'));
    }
}
