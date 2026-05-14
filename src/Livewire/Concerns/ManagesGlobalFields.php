<?php

namespace ArcheeNic\PermissionRegistry\Livewire\Concerns;

use ArcheeNic\PermissionRegistry\Actions\UpdateVirtualUserGlobalFieldsAction;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Support\FieldMetaOption;
use ArcheeNic\PermissionRegistry\Support\FieldMetaOptionRegistry;

trait ManagesGlobalFields
{
    public $globalFields = [];

    /** @var array<int, array<string, mixed>> */
    public $globalFieldsMeta = [];

    public $showGlobalFields = false;

    public function toggleGlobalFields()
    {
        $this->showGlobalFields = ! $this->showGlobalFields;
    }

    public function saveGlobalFields()
    {
        $this->clearFlashMessages();

        if (! $this->selectedUserId) {
            return;
        }

        try {
            $updateAction = app(UpdateVirtualUserGlobalFieldsAction::class);
            $updateAction->execute($this->selectedUserId, $this->globalFields, $this->globalFieldsMeta);
        } catch (\DomainException $e) {
            $this->setFlashError($e->getMessage());

            return;
        }

        $this->selectUser($this->selectedUserId);

        $this->setFlashMessage(__('permission-registry::Global fields updated successfully'));
    }

    public function getGlobalFieldDefinitionsProperty()
    {
        return PermissionField::where(PermissionField::IS_GLOBAL, true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, FieldMetaOption>
     */
    public function metaOptionsForField(PermissionField $field): array
    {
        return app(FieldMetaOptionRegistry::class)->forFieldType($field->type);
    }
}
