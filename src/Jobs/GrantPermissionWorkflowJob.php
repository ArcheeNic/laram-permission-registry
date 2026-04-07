<?php

namespace ArcheeNic\PermissionRegistry\Jobs;

use ArcheeNic\PermissionRegistry\Events\AfterPermissionGranted;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Services\PermissionTriggerExecutor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class GrantPermissionWorkflowJob implements ShouldQueue
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
            // Выполнить цепочку триггеров
            $success = $executor->executeChain($grantedPermission, 'grant');

            // Если успешно, диспетчеризовать событие
            if ($success) {
                Event::dispatch(new AfterPermissionGranted(
                    $grantedPermission->virtual_user_id,
                    $grantedPermission->permission_id,
                    $grantedPermission->permission->name,
                    $grantedPermission->permission->service
                ));
            }
        } catch (\Exception $e) {
            Log::error('Grant permission workflow failed', [
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
