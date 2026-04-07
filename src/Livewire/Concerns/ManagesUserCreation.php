<?php

namespace ArcheeNic\PermissionRegistry\Livewire\Concerns;

use ArcheeNic\PermissionRegistry\Actions\SearchDuplicateFieldValuesAction;
use ArcheeNic\PermissionRegistry\Actions\UpdateVirtualUserGlobalFieldsAction;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;

trait ManagesUserCreation
{
    public $showCreateForm = false;
    public $newUserFields = [];
    public $requiredFields = null;
    public array $duplicateHints = [];

    public function toggleCreateForm()
    {
        $this->showCreateForm = !$this->showCreateForm;
        $this->duplicateHints = [];

        if ($this->showCreateForm) {
            $this->requiredFields = PermissionField::where(PermissionField::IS_GLOBAL, true)
                ->where(PermissionField::REQUIRED_ON_USER_CREATE, true)
                ->orderBy('name')
                ->get()
                ->toArray();

            $this->newUserFields = [];
        }
    }

    public function updatedNewUserFields($value, string $key): void
    {
        $fieldId = (int) $key;

        $allowedFieldIds = collect($this->requiredFields ?? [])->pluck('id')->all();
        if (! in_array($fieldId, $allowedFieldIds, false)) {
            return;
        }

        if (! is_string($value) || trim($value) === '') {
            unset($this->duplicateHints[$fieldId]);

            return;
        }

        $action = app(SearchDuplicateFieldValuesAction::class);
        $count = $action->execute($fieldId, $value);

        if ($count > 0) {
            $this->duplicateHints[$fieldId] = $count;
        } else {
            unset($this->duplicateHints[$fieldId]);
        }
    }

    public function createUser()
    {
        $this->clearFlashMessages();

        if (!$this->requiredFields || empty($this->requiredFields)) {
            $this->setFlashError(__('permission-registry::No required fields configured. Please add global fields with "Required on user create" option.'));
            return;
        }

        $rules = [];
        foreach ($this->requiredFields as $field) {
            $rules['newUserFields.' . $field['id']] = 'required';
        }

        if (!empty($rules)) {
            $this->validate($rules, [
                'newUserFields.*.required' => __('permission-registry::This field is required'),
            ]);
        }

        $user = VirtualUser::create([
            'name' => 'User #' . uniqid(),
            'status' => VirtualUserStatus::DEACTIVATED,
        ]);

        if (!empty($this->newUserFields)) {
            $updateAction = app(UpdateVirtualUserGlobalFieldsAction::class);
            $updateAction->execute($user->id, $this->newUserFields);
        }

        $this->showCreateForm = false;
        $this->newUserFields = [];
        $this->requiredFields = [];
        $this->duplicateHints = [];
        $this->dispatch('refreshUsers');

        $this->selectUser($user->id);

        $this->setFlashMessage(__('permission-registry::User created successfully'));
    }
}
