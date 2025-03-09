<?php

namespace App\Modules\PermissionRegistry\Actions;

use App\Modules\PermissionRegistry\Events\UserGroupChanged;
use App\Modules\PermissionRegistry\Models\UserGroup;
use Illuminate\Support\Facades\Event;

class AssignUserGroupAction
{
    public function handle(int $userId, int $groupId): UserGroup
    {
        $userGroup = UserGroup::create([
            'user_id' => $userId,
            'permission_group_id' => $groupId,
        ]);

        Event::dispatch(new UserGroupChanged(
            $userId,
            $groupId,
            true
        ));

        return $userGroup;
    }

    public function remove(int $userId, int $groupId): bool
    {
        $userGroup = UserGroup::where('user_id', $userId)
            ->where('permission_group_id', $groupId)
            ->first();

        if (!$userGroup) {
            return false;
        }

        $result = $userGroup->delete();

        Event::dispatch(new UserGroupChanged(
            $userId,
            $groupId,
            false
        ));

        return $result;
    }
}
