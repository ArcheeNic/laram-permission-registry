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
        
        // Sort permissions ONCE; iterate all entries per permission (each entry may be a different resource).
        try {
            $sortedIds = $dependencyResolver->sortByDependencies(array_values(array_unique($permissionIds)), 'grant');
        } catch (\RuntimeException $e) {
            Log::error('Failed to sort permissions by dependencies', [
                'user_id' => $this->userId,
                'error' => $e->getMessage()
            ]);
            return;
        }
        
        // Выдать права последовательно с синхронным выполнением триггеров
        foreach ($sortedIds as $permId) {
            $entries = collect($this->permissionsData)
                ->where('permissionId', $permId)
                ->values();

            if ($entries->isEmpty()) {
                continue;
            }

            foreach ($entries as $data) {
                try {
                    Log::debug('GrantMultiplePermissionsJob: выдача права', [
                        'user_id' => $this->userId,
                        'permission_id' => $permId,
                        'resource_id' => $data['resourceId'] ?? null,
                    ]);

                    $grantAction->handle(
                        userId: $this->userId,
                        permissionId: $permId,
                        fieldValues: $data['fieldValues'] ?? [],
                        meta: $data['meta'] ?? [],
                        expiresAt: $data['expiresAt'] ?? null,
                        skipTriggers: false,
                        executeTriggersSync: true,
                        resourceId: $data['resourceId'] ?? null,
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
                        'resource_id' => $data['resourceId'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
