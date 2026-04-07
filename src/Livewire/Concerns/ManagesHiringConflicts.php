<?php

namespace ArcheeNic\PermissionRegistry\Livewire\Concerns;

use ArcheeNic\PermissionRegistry\Actions\ResolveEmailConflictAction;
use ArcheeNic\PermissionRegistry\Enums\HrTriggerExecutionStatus;
use ArcheeNic\PermissionRegistry\Models\HrTriggerExecutionLog;
use Illuminate\Support\Facades\Auth;

trait ManagesHiringConflicts
{
    public bool $showHireConflictModal = false;
    public ?int $selectedHrConflictLogId = null;
    public array $selectedHrConflictMeta = [];
    public string $hireConflictStrategy = 'increment';
    public string $hireConflictCustomEmail = '';
    public bool $selectedUserHasPendingHrConflict = false;
    public int $selectedUserPendingHrConflictsCount = 0;

    public function openHireConflictModal(): void
    {
        if ($this->selectedHrConflictLogId === null) {
            return;
        }

        $this->showHireConflictModal = true;
    }

    public function closeHireConflictModal(): void
    {
        $this->showHireConflictModal = false;
        $this->hireConflictStrategy = 'increment';
        $this->hireConflictCustomEmail = '';
    }

    public function resolveHireConflict(): void
    {
        if ($this->selectedHrConflictLogId === null) {
            return;
        }
        if ($this->selectedUserId === null) {
            return;
        }

        $belongsToUser = HrTriggerExecutionLog::query()
            ->where('id', $this->selectedHrConflictLogId)
            ->where('virtual_user_id', $this->selectedUserId)
            ->exists();
        if (! $belongsToUser) {
            $this->setFlashError(__('permission-registry::messages.hr_conflict_resolve_failed'));

            return;
        }

        $payload = [];
        $strategy = $this->hireConflictStrategy;
        if ($strategy === 'custom_email') {
            $payload['email'] = $this->hireConflictCustomEmail;
        }

        $resolved = app(ResolveEmailConflictAction::class)->execute(
            $this->selectedHrConflictLogId,
            $strategy,
            $payload,
            Auth::id()
        );

        if (! $resolved) {
            $this->setFlashError(__('permission-registry::messages.hr_conflict_resolve_failed'));

            return;
        }

        if ($strategy === 'cancel') {
            $this->setFlashWarning(__('permission-registry::messages.hr_conflict_hiring_paused'));
        } else {
            $this->setFlashMessage(__('permission-registry::messages.hr_conflict_resolved'));
        }

        $this->closeHireConflictModal();
        $this->refreshSelectedUserHrConflicts();
        if ($this->selectedUserId !== null) {
            $this->selectUser($this->selectedUserId);
        }
        $this->dispatch('refreshUsers');
    }

    protected function refreshSelectedUserHrConflicts(): void
    {
        if (! $this->selectedUserId) {
            $this->selectedUserHasPendingHrConflict = false;
            $this->selectedUserPendingHrConflictsCount = 0;
            $this->selectedHrConflictLogId = null;
            $this->selectedHrConflictMeta = [];

            return;
        }

        $query = HrTriggerExecutionLog::query()
            ->where('virtual_user_id', $this->selectedUserId)
            ->where('status', HrTriggerExecutionStatus::AWAITING_RESOLUTION->value)
            ->latest('id');

        $latestConflict = $query->first();
        $count = (clone $query)->count();

        $this->selectedUserHasPendingHrConflict = $latestConflict !== null;
        $this->selectedUserPendingHrConflictsCount = $count;
        $this->selectedHrConflictLogId = $latestConflict?->id;
        $this->selectedHrConflictMeta = (array) ($latestConflict?->meta ?? []);
    }
}
