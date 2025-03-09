<?php

namespace App\Modules\PermissionRegistry\Livewire;

use App\Modules\PermissionRegistry\Models\PermissionField;
use Livewire\Component;
use Livewire\WithPagination;

class FieldsList extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 15;
    public $confirmingDelete = false;
    public $fieldToDelete = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 15],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function confirmDelete($fieldId)
    {
        $this->confirmingDelete = true;
        $this->fieldToDelete = $fieldId;
    }

    public function deleteField()
    {
        if ($this->fieldToDelete) {
            $field = PermissionField::find($this->fieldToDelete);
            if ($field) {
                $field->delete();
                session()->flash('success', __('permission-registry::Field deleted successfully'));
            }
        }

        $this->confirmingDelete = false;
        $this->fieldToDelete = null;
    }

    public function cancelDelete()
    {
        $this->confirmingDelete = false;
        $this->fieldToDelete = null;
    }

    public function render()
    {
        $fields = PermissionField::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', "%{$this->search}%");
            })
            ->paginate($this->perPage);

        return view('permission-registry::livewire.fields-list', compact('fields'));
    }
}
