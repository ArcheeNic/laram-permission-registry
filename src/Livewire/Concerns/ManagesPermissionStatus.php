<?php

namespace ArcheeNic\PermissionRegistry\Livewire\Concerns;

use ArcheeNic\PermissionRegistry\Actions\GetPermissionExecutionStatusAction;
use ArcheeNic\PermissionRegistry\Jobs\ContinuePermissionGrantingJob;
use ArcheeNic\PermissionRegistry\Jobs\ContinuePermissionRevokingJob;
use ArcheeNic\PermissionRegistry\Jobs\RetryTriggerWorkflowJob;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use Illuminate\Support\Facades\Log;

trait ManagesPermissionStatus
{
    public array $permissionStatuses = [];
    public array $dependentPermissionStatuses = [];
    public bool $hasPendingPermissions = false;
    public int $totalPermissionsToProcess = 0;
    public int $processedPermissions = 0;

    public array $processingPermissions = [];
    public bool $isProcessing = false;
    public array $completedPermissionsWithErrors = [];
    public array $continueStepFields = [];
    public array $dirtyDependentPermissionSelections = [];

    public function checkPermissionStatus()
    {
        if (!$this->selectedUserId) {
            return;
        }

        $allUserPermissions = GrantedPermission::where('virtual_user_id', $this->selectedUserId)
            ->get()
            ->keyBy('permission_id');

        $this->updateDirectPermissionStatuses($allUserPermissions);
        $this->updateDependentPermissionStatuses($allUserPermissions);
        $this->syncDependentPermissionsFromDb($allUserPermissions);
        $this->updatePendingCounts($allUserPermissions);

        if (!$this->isProcessing) {
            $this->loadPermissionsWithErrors();
        }

        if ($this->isProcessing) {
            $this->pollProcessingPermissions();
        }
    }

    public function retryTrigger(int $grantedPermissionId, int $triggerId, array $manualFieldValues = [])
    {
        $this->clearFlashMessages();

        if (empty($manualFieldValues)) {
            $manualFieldValues = $this->continueStepFields[$grantedPermissionId][$triggerId] ?? [];
        }

        try {
            RetryTriggerWorkflowJob::dispatch($grantedPermissionId, $triggerId, $manualFieldValues);
            $this->setFlashMessage(__('permission-registry::Continue step queued'));

            if (isset($this->continueStepFields[$grantedPermissionId][$triggerId])) {
                unset($this->continueStepFields[$grantedPermissionId][$triggerId]);
            }

            $this->checkPermissionStatus();
        } catch (\Exception $e) {
            $this->setFlashError($e->getMessage());
            Log::error('Continue step dispatch failed', [
                'granted_permission_id' => $grantedPermissionId,
                'trigger_id' => $triggerId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function continueGranting(int $grantedPermissionId): void
    {
        $this->clearFlashMessages();

        try {
            ContinuePermissionGrantingJob::dispatch($grantedPermissionId);
            $this->setFlashMessage(__('permission-registry::Permission granting resumed'));
            $this->checkPermissionStatus();
        } catch (\Exception $e) {
            $this->setFlashError($e->getMessage());
            Log::error('Continue granting dispatch failed', [
                'granted_permission_id' => $grantedPermissionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function continueRevoking(int $grantedPermissionId): void
    {
        $this->clearFlashMessages();

        try {
            ContinuePermissionRevokingJob::dispatch($grantedPermissionId);
            $this->setFlashMessage(__('permission-registry::Permission revoking resumed'));
            $this->checkPermissionStatus();
        } catch (\Exception $e) {
            $this->setFlashError($e->getMessage());
            Log::error('Continue revoking dispatch failed', [
                'granted_permission_id' => $grantedPermissionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function initializeProcessingTracking(): void
    {
        if (!$this->selectedUserId) {
            return;
        }

        $currentPermissions = GrantedPermission::where('virtual_user_id', $this->selectedUserId)
            ->where('enabled', true)
            ->pluck('permission_id')
            ->toArray();

        $trackedGrantPermissionIds = [];
        $trackedRevokePermissionIds = [];

        foreach ($this->selectedPermissions as $permId => $isSelected) {
            if ($isSelected && !in_array($permId, $currentPermissions)) {
                $trackedGrantPermissionIds[] = $permId;
            } elseif (!$isSelected && in_array($permId, $currentPermissions)) {
                $trackedRevokePermissionIds[] = $permId;
            }
        }

        foreach ($this->dependentSelectedPermissions as $permId => $isEnabled) {
            if ($isEnabled && !in_array($permId, $currentPermissions)) {
                $trackedGrantPermissionIds[] = $permId;
            } elseif (!$isEnabled && in_array($permId, $currentPermissions)) {
                $trackedRevokePermissionIds[] = $permId;
            }
        }

        if (empty($trackedGrantPermissionIds) && empty($trackedRevokePermissionIds)) {
            $this->isProcessing = false;
            $this->processingPermissions = [];
            return;
        }

        $grantPermissions = Permission::with(['triggerAssignments' => function ($query) {
            $query->where('event_type', 'grant')->where('is_enabled', true)->with('trigger');
        }])->whereIn('id', $trackedGrantPermissionIds)->get();

        $revokePermissions = Permission::with(['triggerAssignments' => function ($query) {
            $query->where('event_type', 'revoke')->where('is_enabled', true)->with('trigger');
        }])->whereIn('id', $trackedRevokePermissionIds)->get();

        $this->processingPermissions = [];

        foreach ($grantPermissions->merge($revokePermissions) as $permission) {
            if ($permission->triggerAssignments->isNotEmpty()) {
                $triggers = [];
                foreach ($permission->triggerAssignments as $assignment) {
                    $triggers[] = [
                        'trigger_id' => $assignment->trigger->id,
                        'name' => $assignment->trigger->name,
                        'status' => 'pending',
                        'error_message' => null,
                    ];
                }

                $this->processingPermissions[$permission->id] = [
                    'status' => 'processing',
                    'permission_name' => $permission->name,
                    'triggers' => $triggers,
                ];
            }
        }

        $this->isProcessing = !empty($this->processingPermissions);
    }

    private function updateDirectPermissionStatuses($allUserPermissions): void
    {
        foreach ($this->permissionStatuses as $permissionId => $statusData) {
            $grantedPermission = $allUserPermissions->get($permissionId);
            if ($grantedPermission) {
                $this->permissionStatuses[$permissionId] = [
                    'status' => $grantedPermission->status ?? 'granted',
                    'status_message' => $grantedPermission->status_message,
                ];
            }
        }
    }

    private function updateDependentPermissionStatuses($allUserPermissions): void
    {
        foreach ($this->dependentPermissionStatuses as $permissionId => $statusData) {
            $grantedPermission = $allUserPermissions->get($permissionId);
            if ($grantedPermission) {
                $this->dependentPermissionStatuses[$permissionId] = [
                    'status' => $grantedPermission->status ?? 'granted',
                    'status_message' => $grantedPermission->status_message,
                ];
            }
        }
    }

    private function updatePendingCounts($allUserPermissions): void
    {
        $pendingCount = 0;
        $completedCount = 0;
        $totalCount = 0;

        foreach ($allUserPermissions as $permission) {
            if ($permission->enabled) {
                $totalCount++;
                if (in_array($permission->status, ['pending', 'granting', 'revoking'])) {
                    $pendingCount++;
                } else {
                    $completedCount++;
                }
            }
        }

        $this->hasPendingPermissions = $pendingCount > 0;
        $this->totalPermissionsToProcess = $totalCount;
        $this->processedPermissions = $completedCount;
    }

    private function pollProcessingPermissions(): void
    {
        $trackedPermissionIds = array_keys($this->processingPermissions);

        if (empty($trackedPermissionIds)) {
            $this->isProcessing = false;
            return;
        }

        $statusAction = app(GetPermissionExecutionStatusAction::class);
        $statuses = $statusAction->execute($this->selectedUserId, $trackedPermissionIds);

        foreach ($statuses as $permissionId => $statusData) {
            $this->processingPermissions[$permissionId] = $statusData;
        }

        $allCompleted = true;
        $hasErrors = false;
        $hasPartialSuccess = false;

        foreach ($this->processingPermissions as $data) {
            if ($data['status'] === 'processing') {
                $allCompleted = false;
            } elseif ($data['status'] === 'failed') {
                $hasErrors = true;
            } elseif (isset($data['triggers'])) {
                foreach ($data['triggers'] as $trigger) {
                    if ($trigger['status'] === 'failed') {
                        $hasPartialSuccess = true;
                    }
                }
            }
        }

        if ($allCompleted) {
            $this->finalizeProcessing($hasErrors, $hasPartialSuccess);
        }
    }

    private function finalizeProcessing(bool $hasErrors, bool $hasPartialSuccess): void
    {
        $errorsToKeep = [];
        foreach ($this->processingPermissions as $permissionId => $data) {
            $hasFailedTriggers = false;
            if (isset($data['triggers'])) {
                foreach ($data['triggers'] as $trigger) {
                    if ($trigger['status'] === 'failed') {
                        $hasFailedTriggers = true;
                        break;
                    }
                }
            }

            if ($hasFailedTriggers || $data['status'] === 'failed') {
                $errorsToKeep[$permissionId] = $data;
            }
        }

        $this->isProcessing = false;

        if ($hasErrors) {
            $this->setFlashError(__('permission-registry::Some permissions failed to process'));
        } elseif ($hasPartialSuccess) {
            $this->setFlashWarning(__('permission-registry::Permissions processed with some errors'));
        } else {
            $this->setFlashMessage(__('permission-registry::All permissions processed successfully'));
        }

        $this->refreshPermissionCheckboxes();
        $this->completedPermissionsWithErrors = $errorsToKeep;
    }

    private function loadPermissionsWithErrors(): void
    {
        if (!$this->selectedUserId) {
            return;
        }

        $allUserPermissions = GrantedPermission::where('virtual_user_id', $this->selectedUserId)
            ->where('enabled', true)
            ->get();

        if ($allUserPermissions->isEmpty()) {
            $this->completedPermissionsWithErrors = [];
            return;
        }

        $permissionIds = $allUserPermissions->pluck('permission_id')->toArray();

        $statusAction = app(GetPermissionExecutionStatusAction::class);
        $statuses = $statusAction->execute($this->selectedUserId, $permissionIds);

        $this->completedPermissionsWithErrors = array_filter($statuses, function ($statusData) {
            if (isset($statusData['status']) && $statusData['status'] === 'failed') {
                return true;
            }

            if (isset($statusData['triggers']) && is_array($statusData['triggers'])) {
                foreach ($statusData['triggers'] as $trigger) {
                    if (isset($trigger['status']) && $trigger['status'] === 'failed') {
                        return true;
                    }
                }
            }

            return false;
        });
    }

    private function syncDependentPermissionsFromDb($allUserPermissions): void
    {
        $dependentPermissions = $this->getDependentPermissionsProperty();

        foreach ($dependentPermissions as $permission) {
            $permId = $permission['id'];
            $isDirty = (bool) ($this->dirtyDependentPermissionSelections[$permId] ?? false);
            if ($isDirty && !$this->isProcessing) {
                // Не перетираем локальный выбор пользователя до явного сохранения.
                continue;
            }
            $grantedPermission = $allUserPermissions->get($permId);

            if ($grantedPermission && $grantedPermission->enabled) {
                $this->dependentSelectedPermissions[$permId] = true;
                $this->dependentPermissionStatuses[$permId] = [
                    'status' => $grantedPermission->status ?? 'granted',
                    'status_message' => $grantedPermission->status_message,
                ];
            } elseif ($grantedPermission && !$grantedPermission->enabled) {
                $this->dependentSelectedPermissions[$permId] = false;
            } elseif (!isset($this->dependentSelectedPermissions[$permId])) {
                $this->dependentSelectedPermissions[$permId] = false;
            }
        }
    }

    private function refreshPermissionCheckboxes(): void
    {
        if (!$this->selectedUserId) {
            return;
        }

        $activePermissions = GrantedPermission::where('virtual_user_id', $this->selectedUserId)
            ->where('enabled', true)
            ->pluck('permission_id')
            ->toArray();

        $availablePermissionIds = $this->availablePermissions->pluck('id')->toArray();
        foreach ($availablePermissionIds as $permId) {
            $this->selectedPermissions[$permId] = in_array($permId, $activePermissions);
        }

        $dependentPermissions = $this->getDependentPermissionsProperty();
        foreach ($dependentPermissions as $permission) {
            $this->dependentSelectedPermissions[$permission['id']] = in_array($permission['id'], $activePermissions);
        }
        $this->dirtyDependentPermissionSelections = [];

        $allUserPermissions = GrantedPermission::where('virtual_user_id', $this->selectedUserId)
            ->get()
            ->keyBy('permission_id');

        foreach ($this->permissionStatuses as $permissionId => $statusData) {
            $grantedPermission = $allUserPermissions->get($permissionId);
            if ($grantedPermission && $grantedPermission->enabled) {
                $this->permissionStatuses[$permissionId] = [
                    'status' => $grantedPermission->status ?? 'granted',
                    'status_message' => $grantedPermission->status_message,
                ];
            } else {
                unset($this->permissionStatuses[$permissionId]);
            }
        }

        foreach ($this->dependentPermissionStatuses as $permissionId => $statusData) {
            $grantedPermission = $allUserPermissions->get($permissionId);
            if ($grantedPermission && $grantedPermission->enabled) {
                $this->dependentPermissionStatuses[$permissionId] = [
                    'status' => $grantedPermission->status ?? 'granted',
                    'status_message' => $grantedPermission->status_message,
                ];
            } else {
                unset($this->dependentPermissionStatuses[$permissionId]);
            }
        }

        $this->hasPendingPermissions = false;
        foreach ($allUserPermissions as $permission) {
            if ($permission->enabled && in_array($permission->status, ['pending', 'granting', 'revoking'])) {
                $this->hasPendingPermissions = true;
                break;
            }
        }
    }
}
