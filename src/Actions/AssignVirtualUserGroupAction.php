<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Events\VirtualUserGroupChanged;
use ArcheeNic\PermissionRegistry\Models\VirtualUserGroup;
use Illuminate\Support\Facades\Event;

class AssignVirtualUserGroupAction
{
    public function handle(int $userId, int $groupId): VirtualUserGroup
    {
        $userGroup = VirtualUserGroup::create([
            'virtual_user_id' => $userId,
            'permission_group_id' => $groupId,
        ]);

        Event::dispatch(new VirtualUserGroupChanged(
            $userId,
            $groupId,
            true
        ));

        return $userGroup;
    }

    public function remove(int $userId, int $groupId): bool
    {
        $userGroup = VirtualUserGroup::where('virtual_user_id', $userId)
            ->where('permission_group_id', $groupId)
            ->first();

        if (!$userGroup) {
            return false;
        }

        $result = $userGroup->delete();

        Event::dispatch(new VirtualUserGroupChanged(
            $userId,
            $groupId,
            false
        ));

        return $result;
    }
}
