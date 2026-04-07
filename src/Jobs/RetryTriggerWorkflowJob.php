<?php

namespace ArcheeNic\PermissionRegistry\Jobs;

use ArcheeNic\PermissionRegistry\Actions\RetryTriggerFromFailedAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetryTriggerWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $grantedPermissionId,
        private int $failedTriggerId,
        private array $manualFieldValues = []
    ) {
    }

    public function handle(RetryTriggerFromFailedAction $action): void
    {
        try {
            $success = $action->execute(
                $this->grantedPermissionId,
                $this->failedTriggerId,
                $this->manualFieldValues
            );

            if (!$success) {
                Log::warning('Retry trigger workflow completed with failure', [
                    'granted_permission_id' => $this->grantedPermissionId,
                    'failed_trigger_id' => $this->failedTriggerId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Retry trigger workflow failed', [
                'granted_permission_id' => $this->grantedPermissionId,
                'failed_trigger_id' => $this->failedTriggerId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
