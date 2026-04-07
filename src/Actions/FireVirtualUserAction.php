<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Services\HrEventTriggerExecutor;
use Illuminate\Support\Facades\DB;

class FireVirtualUserAction
{
    public function __construct(
        private ReconcileUserPermissionsAction $reconcileUserPermissionsAction,
        private ProcessFireRevocationsAction $processFireRevocationsAction,
        private HrEventTriggerExecutor $hrEventTriggerExecutor
    ) {
    }

    public function handle(int $userId, bool $skipHrTriggers = false): VirtualUser
    {
        $user = DB::transaction(function () use ($userId): VirtualUser {
            $user = VirtualUser::query()->findOrFail($userId);

            $user->positions()->detach();
            $user->groups()->detach();

            $user->status = VirtualUserStatus::DEACTIVATED;
            $user->save();

            $this->reconcileUserPermissionsAction->handle($user->id);
            $this->processFireRevocationsAction->handle($user->id);

            return $user->fresh();
        });

        if (!$skipHrTriggers) {
            $this->hrEventTriggerExecutor->execute($user->id, 'fire');
        }

        return $user;
    }
}
