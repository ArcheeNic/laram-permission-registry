<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Actions\AuditLogger;
use ArcheeNic\PermissionRegistry\Enums\GrantedPermissionStatus;
use ArcheeNic\PermissionRegistry\Events\AfterPermissionRevoked;
use ArcheeNic\PermissionRegistry\Events\BeforePermissionRevoked;
use ArcheeNic\PermissionRegistry\Jobs\RevokePermissionWorkflowJob;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Services\PermissionTriggerExecutor;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class RevokePermissionAction
{
    public function __construct(
        private PermissionTriggerExecutor $triggerExecutor,
        private AuditLogger $auditLogger
    ) {
    }

    public function handle(
        int $userId, 
        int $permissionId,
        bool $skipTriggers = false,
        bool $executeTriggersSync = false
    ): bool {
        $permission = Permission::findOrFail($permissionId);

        // Поиск выданного доступа
        $grantedPermission = GrantedPermission::where('virtual_user_id', $userId)
            ->where('permission_id', $permissionId)
            ->first();

        if (!$grantedPermission) {
            return false;
        }

        // Диспетчеризация события перед отзывом доступа
        Event::dispatch(new BeforePermissionRevoked(
            $userId,
            $permissionId,
            $permission->name,
            $permission->service
        ));

        if ($this->shouldForceDeleteBrokenPermission($grantedPermission)) {
            return $this->deleteGrantedPermissionAndFinalize(
                $grantedPermission,
                $userId,
                $permissionId,
                $permission->name,
                $permission->service
            );
        }

        if ($skipTriggers) {
            return $this->deleteGrantedPermissionAndFinalize(
                $grantedPermission,
                $userId,
                $permissionId,
                $permission->name,
                $permission->service
            );
        }

        // Обновить статус на "отзывается"
        $grantedPermission->update([
            'status' => GrantedPermissionStatus::REVOKING->value,
        ]);

        if ($executeTriggersSync) {
            // Синхронное выполнение триггеров
            try {
                $success = $this->triggerExecutor->executeChain($grantedPermission, 'revoke');
                
                if ($success) {
                    $this->deleteGrantedPermissionAndFinalize(
                        $grantedPermission,
                        $userId,
                        $permissionId,
                        $permission->name,
                        $permission->service
                    );
                }
                
                return $success;
            } catch (\Exception $e) {
                Log::error('Revoke permission workflow failed (sync)', [
                    'granted_permission_id' => $grantedPermission->id,
                    'error' => $e->getMessage(),
                ]);
                
                $grantedPermission->update([
                    'status' => 'failed',
                    'status_message' => $e->getMessage(),
                ]);
                
                throw $e;
            }
        } else {
            // Асинхронное выполнение через очередь
            RevokePermissionWorkflowJob::dispatch($grantedPermission->id);
            return true;
        }
    }

    private function shouldForceDeleteBrokenPermission(GrantedPermission $grantedPermission): bool
    {
        return in_array($grantedPermission->status, [
            GrantedPermissionStatus::FAILED->value,
            GrantedPermissionStatus::PARTIALLY_GRANTED->value,
            GrantedPermissionStatus::PARTIALLY_REVOKED->value,
        ], true);
    }

    private function deleteGrantedPermissionAndFinalize(
        GrantedPermission $grantedPermission,
        int $userId,
        int $permissionId,
        string $permissionName,
        string $permissionService
    ): bool {
        $result = $grantedPermission->delete();

        Event::dispatch(new AfterPermissionRevoked(
            $userId,
            $permissionId,
            $permissionName,
            $permissionService
        ));

        $this->auditLogger->log('permission.revoked', null, [
            'virtual_user_id' => $userId,
            'permission_id' => $permissionId,
            'permission_name' => $permissionName,
            'service' => $permissionService,
        ]);

        return $result;
    }
}
