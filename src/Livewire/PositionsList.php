<?php

namespace App\Modules\PermissionRegistry\Livewire;

use App\Modules\PermissionRegistry\Models\Position;
use Livewire\Component;
use Livewire\WithPagination;

class PositionsList extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 15;
    public $confirmingDelete = false;
    public $positionToDelete = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 15],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function confirmDelete($positionId)
    {
        $this->confirmingDelete = true;
        $this->positionToDelete = $positionId;
    }

    public function deletePosition()
    {
        if ($this->positionToDelete) {
            $position = Position::find($this->positionToDelete);
            if ($position) {
                $position->delete();
                session()->flash('success', __('permission-registry::Position deleted successfully'));
            }
        }

        $this->confirmingDelete = false;
        $this->positionToDelete = null;
    }

    public function cancelDelete()
    {
        $this->confirmingDelete = false;
        $this->positionToDelete = null;
    }

    public function render()
    {
        $positions = Position::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                });
            })
            ->with('parent')
            ->withCount(['permissions', 'groups', 'users'])
            ->paginate($this->perPage);

        return view('permission-registry::livewire.positions-list', compact('positions'));
    }
}
