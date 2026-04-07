<?php

namespace ArcheeNic\PermissionRegistry\Jobs;

use ArcheeNic\PermissionRegistry\Actions\ContinuePermissionGrantingAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ContinuePermissionGrantingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $grantedPermissionId
    ) {
    }

    public function handle(ContinuePermissionGrantingAction $action): void
    {
        try {
            $success = $action->execute($this->grantedPermissionId);

            if (!$success) {
                Log::warning('Continue permission granting completed with failure', [
                    'granted_permission_id' => $this->grantedPermissionId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Continue permission granting job failed', [
                'granted_permission_id' => $this->grantedPermissionId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
