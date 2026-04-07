<?php

namespace ArcheeNic\PermissionRegistry\Jobs;

use ArcheeNic\PermissionRegistry\Events\AfterPermissionRevoked;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Services\PermissionTriggerExecutor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class RevokePermissionWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $grantedPermissionId
    ) {
    }

    public function handle(PermissionTriggerExecutor $executor): void
    {
        $grantedPermission = GrantedPermission::with('permission')->find($this->grantedPermissionId);

        if (!$grantedPermission) {
            Log::error('GrantedPermission not found', ['id' => $this->grantedPermissionId]);
            return;
        }

        try {
            // Выполнить цепочку триггеров отзыва
            $success = $executor->executeChain($grantedPermission, 'revoke');

            // Если успешно, удалить запись и диспетчеризовать событие
            if ($success) {
                $virtualUserId = $grantedPermission->virtual_user_id;
                $permissionId = $grantedPermission->permission_id;
                $permissionName = $grantedPermission->permission->name;
                $permissionService = $grantedPermission->permission->service;

                $grantedPermission->delete();

                Event::dispatch(new AfterPermissionRevoked(
                    $virtualUserId,
                    $permissionId,
                    $permissionName,
                    $permissionService
                ));
            }
        } catch (\Exception $e) {
            Log::error('Revoke permission workflow failed', [
                'granted_permission_id' => $this->grantedPermissionId,
                'error' => $e->getMessage(),
            ]);

            $grantedPermission->update([
                'status' => 'failed',
                'status_message' => $e->getMessage(),
            ]);
        }
    }
}
