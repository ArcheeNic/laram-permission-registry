<?php

namespace ArcheeNic\PermissionRegistry\Livewire;

use ArcheeNic\PermissionRegistry\Models\Position;
use Livewire\Component;
use Livewire\WithPagination;

class PositionsList extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 15;
    public $confirmingDelete = false;
    public $positionToDelete = null;
    public $openPositions = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 15],
        'openPositions' => ['except' => []],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function togglePosition($positionId)
    {
        if (in_array($positionId, $this->openPositions)) {
            $this->openPositions = array_values(array_diff($this->openPositions, [$positionId]));
        } else {
            $this->openPositions[] = $positionId;
        }
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
            ->whereNull('parent_id')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                });
            })
            ->with([
                'parent',
                'children' => fn ($query) => $this->loadChildrenTreeWithCounts($query),
            ])
            ->withCount(['permissions', 'groups', 'users'])
            ->paginate($this->perPage);

        return view('permission-registry::livewire.positions-list', compact('positions'));
    }

    private function loadChildrenTreeWithCounts($query): void
    {
        $query
            ->withCount(['permissions', 'groups', 'users'])
            ->with([
                'children' => fn ($childrenQuery) => $this->loadChildrenTreeWithCounts($childrenQuery),
            ]);
    }
}
