<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use Illuminate\Support\Facades\Log;

class ContinuePermissionRevokingAction
{
    public function __construct(
        private RetryTriggerFromFailedAction $retryAction
    ) {
    }

    /**
     * Продолжить отзыв права с последнего упавшего триггера
     *
     * @param int $grantedPermissionId
     * @return bool
     */
    public function execute(int $grantedPermissionId): bool
    {
        $grantedPermission = GrantedPermission::with('executionLogs')->find($grantedPermissionId);

        if (!$grantedPermission) {
            Log::error('GrantedPermission not found for continue revoking', [
                'granted_permission_id' => $grantedPermissionId,
            ]);
            return false;
        }

        $failedTriggerId = $this->getFailedTriggerId($grantedPermission);

        if (!$failedTriggerId) {
            Log::warning('No failed trigger found to continue revoking', [
                'granted_permission_id' => $grantedPermissionId,
            ]);
            return false;
        }

        return $this->retryAction->execute($grantedPermissionId, $failedTriggerId);
    }

    private function getFailedTriggerId(GrantedPermission $grantedPermission): ?int
    {
        $meta = $grantedPermission->meta ?? [];
        if (!empty($meta['last_failed_trigger_id'])) {
            return (int) $meta['last_failed_trigger_id'];
        }

        $lastFailedLog = $grantedPermission->executionLogs()
            ->where('status', 'failed')
            ->orderByDesc('created_at')
            ->first();

        return $lastFailedLog?->permission_trigger_id;
    }
}
