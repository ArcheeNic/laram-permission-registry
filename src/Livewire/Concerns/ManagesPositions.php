<?php

namespace ArcheeNic\PermissionRegistry\Livewire\Concerns;

use ArcheeNic\PermissionRegistry\Actions\AssignVirtualUserPositionAction;
use ArcheeNic\PermissionRegistry\Actions\BulkAssignVirtualUserPositionAction;
use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Models\VirtualUserPosition;

trait ManagesPositions
{
    public $selectedPosition = '';

    public function isPositionSelected(): bool
    {
        return !empty($this->selectedPosition);
    }

    public function assignPosition()
    {
        $this->validate([
            'selectedUserId' => 'required',
            'selectedPosition' => 'required',
        ]);

        $action = app(AssignVirtualUserPositionAction::class);
        $action->handle($this->selectedUserId, $this->selectedPosition);

        $this->selectedPosition = null;

        $this->selectUser($this->selectedUserId);
        $this->hasPendingPermissions = true;

        $this->dispatch('refreshUsers');
    }

    public function removePosition($positionId)
    {
        if (!$this->selectedUserId) {
            return;
        }

        $action = app(AssignVirtualUserPositionAction::class);
        $action->remove($this->selectedUserId, $positionId);

        $this->selectUser($this->selectedUserId);
        $this->dispatch('refreshUsers');
    }

    public function getPositionsProperty()
    {
        if (!$this->selectedUserId) {
            return collect();
        }

        $assignedPositionIds = VirtualUserPosition::where('virtual_user_id', $this->selectedUserId)
            ->pluck('position_id')
            ->toArray();

        return Position::with('parent.parent.parent.parent')
            ->whereNotIn('id', $assignedPositionIds)
            ->orderBy('name')
            ->get();
    }

    public function bulkAssignPosition(): void
    {
        $this->authorize('permission-registry.manage');

        $validated = $this->validate([
            'bulkSelectedIds' => 'required|array|min:1|max:50',
            'bulkSelectedIds.*' => 'required|integer|distinct|exists:virtual_users,id',
            'bulkPositionId' => 'required|integer|exists:positions,id',
        ]);

        $result = app(BulkAssignVirtualUserPositionAction::class)->handle(
            $validated['bulkSelectedIds'],
            (int) $validated['bulkPositionId']
        );

        $this->applyBulkOperationResult($result, __('permission-registry::messages.assign_position'));
        $this->bulkPositionId = '';
        $this->clearBulkSelection();
        $this->dispatch('refreshUsers');
    }
}
