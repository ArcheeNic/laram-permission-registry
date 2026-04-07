<?php

namespace ArcheeNic\PermissionRegistry\Jobs;

use ArcheeNic\PermissionRegistry\Actions\ContinuePermissionRevokingAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ContinuePermissionRevokingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $grantedPermissionId
    ) {
    }

    public function handle(ContinuePermissionRevokingAction $action): void
    {
        try {
            $success = $action->execute($this->grantedPermissionId);

            if (!$success) {
                Log::warning('Continue permission revoking completed with failure', [
                    'granted_permission_id' => $this->grantedPermissionId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Continue permission revoking job failed', [
                'granted_permission_id' => $this->grantedPermissionId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
