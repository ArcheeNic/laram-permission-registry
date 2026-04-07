<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\GrantedPermissionStatus;
use ArcheeNic\PermissionRegistry\Enums\ManualTaskStatus;
use ArcheeNic\PermissionRegistry\Events\AfterPermissionGranted;
use ArcheeNic\PermissionRegistry\Models\ManualProvisionTask;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

class ConfirmManualProvisionAction
{
    public function __construct(
        private AttachAccessEvidenceAction $attachEvidence,
        private AuditLogger $auditLogger
    ) {}

    public function handle(
        ManualProvisionTask $task,
        int $completedBy,
        array $evidenceData = []
    ): ManualProvisionTask {
        if ($task->status->isTerminal()) {
            throw ValidationException::withMessages([
                'task' => [__('permission-registry::governance.task_already_completed')],
            ]);
        }

        $task->update([
            ManualProvisionTask::STATUS => ManualTaskStatus::COMPLETED->value,
            ManualProvisionTask::COMPLETED_AT => now(),
            ManualProvisionTask::COMPLETED_BY => $completedBy,
        ]);

        if (! empty($evidenceData) && isset($evidenceData['type'], $evidenceData['value'])) {
            $this->attachEvidence->handle(
                grantedPermissionId: $task->granted_permission_id,
                type: $evidenceData['type'],
                value: (string) $evidenceData['value'],
                providedBy: $completedBy,
                manualProvisionTaskId: $task->id,
                meta: isset($evidenceData['meta']) && is_array($evidenceData['meta']) ? $evidenceData['meta'] : null
            );
        }

        $grantedPermission = $task->grantedPermission;
        $grantedPermission->update([
            'status' => GrantedPermissionStatus::GRANTED->value,
            'enabled' => true,
            'confirmed_by' => $completedBy,
            'confirmed_at' => now(),
        ]);

        $permission = $grantedPermission->permission;
        Event::dispatch(new AfterPermissionGranted(
            $grantedPermission->virtual_user_id,
            $permission->id,
            $permission->name,
            $permission->service
        ));

        $this->auditLogger->log('manual_task.completed', $completedBy, [
            'task_id' => $task->id,
            'granted_permission_id' => $grantedPermission->id,
        ]);

        return $task;
    }
}
