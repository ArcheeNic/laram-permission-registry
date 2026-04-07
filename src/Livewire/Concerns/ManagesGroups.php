<?php

namespace ArcheeNic\PermissionRegistry\Livewire\Concerns;

use ArcheeNic\PermissionRegistry\Actions\AssignVirtualUserGroupAction;
use ArcheeNic\PermissionRegistry\Actions\BulkAssignVirtualUserGroupAction;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use ArcheeNic\PermissionRegistry\Models\VirtualUserGroup;

trait ManagesGroups
{
    public $selectedGroup = null;

    public function assignGroup()
    {
        $this->validate([
            'selectedUserId' => 'required',
            'selectedGroup' => 'required',
        ]);

        $action = app(AssignVirtualUserGroupAction::class);
        $action->handle($this->selectedUserId, $this->selectedGroup);

        $this->selectedGroup = null;

        $this->selectUser($this->selectedUserId);
        $this->hasPendingPermissions = true;

        $this->dispatch('refreshUsers');
    }

    public function removeGroup($groupId)
    {
        $action = app(AssignVirtualUserGroupAction::class);
        $action->remove($this->selectedUserId, $groupId);

        $this->selectUser($this->selectedUserId);
        $this->dispatch('refreshUsers');
    }

    public function getGroupsProperty()
    {
        if (!$this->selectedUserId) {
            return collect();
        }

        $userGroupIds = VirtualUserGroup::where('virtual_user_id', $this->selectedUserId)
            ->pluck('permission_group_id');

        return PermissionGroup::whereNotIn('id', $userGroupIds)
            ->orderBy('name')
            ->get();
    }

    public function bulkAssignGroup(): void
    {
        $this->authorize('permission-registry.manage');

        $validated = $this->validate([
            'bulkSelectedIds' => 'required|array|min:1|max:50',
            'bulkSelectedIds.*' => 'required|integer|distinct|exists:virtual_users,id',
            'bulkGroupId' => 'required|integer|exists:permission_groups,id',
        ]);

        $result = app(BulkAssignVirtualUserGroupAction::class)->handle(
            $validated['bulkSelectedIds'],
            (int) $validated['bulkGroupId']
        );

        $this->applyBulkOperationResult($result, __('permission-registry::messages.assign_group'));
        $this->bulkGroupId = '';
        $this->clearBulkSelection();
        $this->dispatch('refreshUsers');
    }
}
