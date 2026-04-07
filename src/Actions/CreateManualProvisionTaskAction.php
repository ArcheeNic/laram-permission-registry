<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\ManualTaskStatus;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\ManualProvisionTask;
use ArcheeNic\PermissionRegistry\Models\Permission;
use Illuminate\Validation\ValidationException;

class CreateManualProvisionTaskAction
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    public function handle(
        GrantedPermission $grantedPermission,
        Permission $permission,
        ?int $assignedTo = null,
        ?string $dueAt = null
    ): ManualProvisionTask {
        $assignee = $assignedTo ?? $permission->system_owner_virtual_user_id;

        if ($dueAt !== null && $dueAt !== '') {
            $parsed = \DateTime::createFromFormat(\DateTimeInterface::ATOM, $dueAt)
                ?: \DateTime::createFromFormat('Y-m-d H:i:s', $dueAt)
                ?: \DateTime::createFromFormat('Y-m-d', $dueAt);
            if ($parsed === false) {
                throw ValidationException::withMessages([
                    'due_at' => [__('permission-registry::governance.invalid_due_at')],
                ]);
            }
        }

        $task = ManualProvisionTask::create([
            ManualProvisionTask::GRANTED_PERMISSION_ID => $grantedPermission->id,
            ManualProvisionTask::ASSIGNED_TO => $assignee,
            ManualProvisionTask::TITLE => __('permission-registry::governance.manual_task_title', [
                'permission' => $permission->name,
                'service' => $permission->service,
            ]),
            ManualProvisionTask::STATUS => ManualTaskStatus::PENDING->value,
            ManualProvisionTask::DUE_AT => $dueAt,
        ]);

        $this->auditLogger->log('manual_task.created', $assignee, [
            'task_id' => $task->id,
            'granted_permission_id' => $grantedPermission->id,
            'permission_name' => $permission->name,
        ]);

        return $task;
    }
}
