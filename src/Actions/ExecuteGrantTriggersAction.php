<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Events\AfterPermissionGranted;
use ArcheeNic\PermissionRegistry\Jobs\GrantPermissionWorkflowJob;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Services\PermissionTriggerExecutor;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class ExecuteGrantTriggersAction
{
    public function __construct(
        private PermissionTriggerExecutor $triggerExecutor
    ) {}

    public function handle(
        GrantedPermission $grantedPermission,
        Permission $permission,
        bool $skipTriggers,
        bool $executeTriggersSync
    ): void {
        if ($skipTriggers) {
            $this->dispatchAfterEvent($grantedPermission, $permission);
            return;
        }

        if ($executeTriggersSync) {
            $this->executeSync($grantedPermission, $permission);
        } else {
            GrantPermissionWorkflowJob::dispatch($grantedPermission->id);
        }
    }

    private function executeSync(GrantedPermission $grantedPermission, Permission $permission): void
    {
        try {
            $success = $this->triggerExecutor->executeChain($grantedPermission, 'grant');

            if ($success) {
                $this->dispatchAfterEvent($grantedPermission, $permission);
            }
        } catch (\Exception $e) {
            Log::error('Grant permission workflow failed (sync)', [
                'granted_permission_id' => $grantedPermission->id,
                'error' => $e->getMessage(),
            ]);

            $grantedPermission->update([
                'status' => 'failed',
                'status_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function dispatchAfterEvent(GrantedPermission $grantedPermission, Permission $permission): void
    {
        Event::dispatch(new AfterPermissionGranted(
            $grantedPermission->virtual_user_id,
            $permission->id,
            $permission->name,
            $permission->service
        ));
    }
}
