<?php

namespace ArcheeNic\PermissionRegistry\Livewire\Concerns;

use ArcheeNic\PermissionRegistry\Actions\BulkFireVirtualUsersAction;
use ArcheeNic\PermissionRegistry\Actions\BulkHireVirtualUsersAction;
use ArcheeNic\PermissionRegistry\Actions\FireVirtualUserAction;
use ArcheeNic\PermissionRegistry\Actions\HireVirtualUserAction;
use ArcheeNic\PermissionRegistry\DataTransferObjects\HrTriggerExecutionResult;
use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Services\HrEventTriggerExecutor;

trait ManagesHiring
{
    public string $selectedHireCategory = EmployeeCategory::STAFF->value;

    public function hireUser(): void
    {
        $this->authorize('permission-registry.manage');

        if (! $this->selectedUserId) {
            return;
        }

        $validated = $this->validate([
            'selectedHireCategory' => 'required|in:'.implode(',', array_column(EmployeeCategory::cases(), 'value')),
        ]);

        app(HireVirtualUserAction::class)->handle(
            $this->selectedUserId,
            [],
            [],
            $validated['selectedHireCategory']
        );

        $this->selectUser($this->selectedUserId);
        $this->dispatch('refreshUsers');

        $failure = app(HrEventTriggerExecutor::class)->getLastResult();
        if ($failure !== null && ! $failure->success) {
            $this->setFlashError($this->formatHrTriggerFailure($failure, 'hire'));

            return;
        }

        $this->setFlashMessage(__('permission-registry::messages.user_hired'));
    }

    public function fireUser(): void
    {
        $this->authorize('permission-registry.manage');

        if (! $this->selectedUserId) {
            return;
        }

        app(FireVirtualUserAction::class)->handle($this->selectedUserId);

        $this->selectUser($this->selectedUserId);
        $this->dispatch('refreshUsers');

        $failure = app(HrEventTriggerExecutor::class)->getLastResult();
        if ($failure !== null && ! $failure->success) {
            $this->setFlashError($this->formatHrTriggerFailure($failure, 'fire'));

            return;
        }

        $this->setFlashWarning(__('permission-registry::messages.user_fired'));
    }

    private function formatHrTriggerFailure(HrTriggerExecutionResult $result, string $eventType): string
    {
        $prefix = $eventType === 'fire'
            ? __('permission-registry::messages.hr_trigger_failed_on_fire')
            : __('permission-registry::messages.hr_trigger_failed_on_hire');

        $triggerName = $result->triggerName ?? __('permission-registry::messages.hr_trigger_unnamed');
        $errorMessage = $result->errorMessage !== null && $result->errorMessage !== ''
            ? $result->errorMessage
            : __('permission-registry::messages.hr_trigger_generic_error');

        return "{$prefix}: «{$triggerName}» — {$errorMessage}";
    }

    public function bulkHireUsers(): void
    {
        $this->authorize('permission-registry.manage');

        $validated = $this->validate([
            'bulkSelectedIds' => 'required|array|min:1|max:50',
            'bulkSelectedIds.*' => 'required|integer|distinct|exists:virtual_users,id',
            'selectedHireCategory' => 'required|in:'.implode(',', array_column(EmployeeCategory::cases(), 'value')),
        ]);

        $result = app(BulkHireVirtualUsersAction::class)->handle(
            $validated['bulkSelectedIds'],
            [],
            [],
            $validated['selectedHireCategory']
        );

        $this->applyBulkOperationResult($result, __('permission-registry::messages.hire'));
        $this->clearBulkSelection();
        $this->dispatch('refreshUsers');
    }

    public function confirmFire(): void
    {
        $this->fireUser();
    }

    public function bulkFireUsers(): void
    {
        $this->authorize('permission-registry.manage');

        $validated = $this->validate([
            'bulkSelectedIds' => 'required|array|min:1|max:50',
            'bulkSelectedIds.*' => 'required|integer|distinct|exists:virtual_users,id',
        ]);

        $result = app(BulkFireVirtualUsersAction::class)->handle($validated['bulkSelectedIds']);

        $this->applyBulkOperationResult($result, __('permission-registry::messages.fire'));
        $this->clearBulkSelection();
        $this->dispatch('refreshUsers');
    }

    public function getSelectedUserStatusLabelProperty(): string
    {
        $status = $this->selectedUser?->status;

        if ($status === VirtualUserStatus::DEACTIVATED) {
            return __('permission-registry::messages.deactivated');
        }

        return __('permission-registry::messages.active');
    }

    /**
     * @return array<string, string>
     */
    public function getEmployeeCategoryOptionsProperty(): array
    {
        $options = [];
        foreach (EmployeeCategory::cases() as $category) {
            $options[$category->value] = __($category->labelKey());
        }

        return $options;
    }
}
