<?php

namespace ArcheeNic\PermissionRegistry\Livewire\Concerns;

use ArcheeNic\PermissionRegistry\Actions\UpdateVirtualUserGlobalFieldsAction;
use ArcheeNic\PermissionRegistry\Models\PermissionField;

trait ManagesGlobalFields
{
    public $globalFields = [];
    public $showGlobalFields = false;

    public function toggleGlobalFields()
    {
        $this->showGlobalFields = !$this->showGlobalFields;
    }

    public function saveGlobalFields()
    {
        $this->clearFlashMessages();

        if (!$this->selectedUserId) {
            return;
        }

        $updateAction = app(UpdateVirtualUserGlobalFieldsAction::class);
        $updateAction->execute($this->selectedUserId, $this->globalFields);

        $this->selectUser($this->selectedUserId);

        $this->setFlashMessage(__('permission-registry::Global fields updated successfully'));
    }

    public function getGlobalFieldDefinitionsProperty()
    {
        return PermissionField::where(PermissionField::IS_GLOBAL, true)
            ->orderBy('name')
            ->get();
    }
}
