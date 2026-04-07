<?php

namespace ArcheeNic\PermissionRegistry\Jobs;

use ArcheeNic\PermissionRegistry\Actions\GrantPermissionAction;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Exceptions\UserDeactivatedException;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Services\PermissionDependencyResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GrantMultiplePermissionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $userId,
        private array $permissionsData
    ) {
    }

    public function handle(
        GrantPermissionAction $grantAction,
        PermissionDependencyResolver $dependencyResolver
    ): void {
        $virtualUser = VirtualUser::find($this->userId);
        if (! $virtualUser || $virtualUser->status === VirtualUserStatus::DEACTIVATED) {
            Log::info('GrantMultiplePermissionsJob: skipped — user deactivated or not found', [
                'user_id' => $this->userId,
                'status' => $virtualUser?->status?->value,
            ]);

            return;
        }

        $permissionIds = array_column($this->permissionsData, 'permissionId');
        
        Log::debug('GrantMultiplePermissionsJob: начало', [
            'user_id' => $this->userId,
            'permission_ids' => $permissionIds
        ]);
        
        // Сортировать по зависимостям для выдачи (grant)
        try {
            $sortedIds = $dependencyResolver->sortByDependencies($permissionIds, 'grant');
        } catch (\RuntimeException $e) {
            Log::error('Failed to sort permissions by dependencies', [
                'user_id' => $this->userId,
                'error' => $e->getMessage()
            ]);
            return;
        }
        
        // Выдать права последовательно с синхронным выполнением триггеров
        foreach ($sortedIds as $permId) {
            $data = collect($this->permissionsData)
                ->firstWhere('permissionId', $permId);
            
            if (!$data) {
                continue;
            }
            
            try {
                Log::debug('GrantMultiplePermissionsJob: выдача права', [
                    'user_id' => $this->userId,
                    'permission_id' => $permId
                ]);
                
                $grantAction->handle(
                    userId: $this->userId,
                    permissionId: $permId,
                    fieldValues: $data['fieldValues'] ?? [],
                    meta: $data['meta'] ?? [],
                    expiresAt: $data['expiresAt'] ?? null,
                    skipTriggers: false,
                    executeTriggersSync: true
                );
            } catch (UserDeactivatedException $e) {
                Log::info('GrantMultiplePermissionsJob: aborted — user deactivated mid-batch', [
                    'user_id' => $this->userId,
                ]);

                return;
            } catch (\Exception $e) {
                Log::error('Failed to grant permission in batch', [
                    'user_id' => $this->userId,
                    'permission_id' => $permId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
