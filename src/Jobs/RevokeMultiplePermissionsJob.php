<?php

namespace ArcheeNic\PermissionRegistry\Jobs;

use ArcheeNic\PermissionRegistry\Actions\RevokePermissionAction;
use ArcheeNic\PermissionRegistry\Services\PermissionDependencyResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RevokeMultiplePermissionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $userId,
        private array $permissionIds
    ) {
    }

    public function handle(
        RevokePermissionAction $revokeAction,
        PermissionDependencyResolver $dependencyResolver
    ): void {
        Log::debug('RevokeMultiplePermissionsJob: начало', [
            'user_id' => $this->userId,
            'permission_ids' => $this->permissionIds
        ]);
        
        // Сортировать по зависимостям для отзыва (revoke)
        // Порядок будет обратный: сначала зависимые права, потом базовые
        try {
            $sortedIds = $dependencyResolver->sortByDependencies($this->permissionIds, 'revoke');
        } catch (\RuntimeException $e) {
            Log::error('Failed to sort permissions by dependencies', [
                'user_id' => $this->userId,
                'error' => $e->getMessage()
            ]);
            return;
        }
        
        // Отзываем права последовательно в правильном порядке
        foreach ($sortedIds as $permId) {
            try {
                Log::debug('RevokeMultiplePermissionsJob: отзыв права', [
                    'user_id' => $this->userId,
                    'permission_id' => $permId
                ]);
                
                $revokeAction->handle(
                    userId: $this->userId,
                    permissionId: $permId,
                    skipTriggers: false,
                    executeTriggersSync: true
                );
                
            } catch (\Exception $e) {
                Log::error('Failed to revoke permission in batch', [
                    'user_id' => $this->userId,
                    'permission_id' => $permId,
                    'error' => $e->getMessage()
                ]);
                // Продолжаем отзыв остальных прав
            }
        }
        
    }
}
