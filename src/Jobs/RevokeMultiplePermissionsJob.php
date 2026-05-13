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

    /**
     * @param  array<int, int|array{permissionId: int, resourceId?: ?int}>  $permissionIds
     *   Backward-compatible: каждый элемент либо целое (permission_id, service-scope revoke),
     *   либо массив `{permissionId, resourceId}` для resource-scoped grants.
     */
    public function __construct(
        private int $userId,
        private array $permissionIds
    ) {
    }

    public function handle(
        RevokePermissionAction $revokeAction,
        PermissionDependencyResolver $dependencyResolver
    ): void {
        $normalized = $this->normalize();

        Log::debug('RevokeMultiplePermissionsJob: начало', [
            'user_id' => $this->userId,
            'entries' => $normalized,
        ]);

        $permissionIds = array_values(array_unique(array_map(
            static fn (array $entry): int => $entry['permissionId'],
            $normalized
        )));

        try {
            $sortedIds = $dependencyResolver->sortByDependencies($permissionIds, 'revoke');
        } catch (\RuntimeException $e) {
            Log::error('Failed to sort permissions by dependencies', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
            ]);
            return;
        }

        foreach ($sortedIds as $permId) {
            foreach ($normalized as $entry) {
                if ($entry['permissionId'] !== $permId) {
                    continue;
                }
                try {
                    Log::debug('RevokeMultiplePermissionsJob: отзыв права', [
                        'user_id' => $this->userId,
                        'permission_id' => $permId,
                        'resource_id' => $entry['resourceId'],
                    ]);

                    $revokeAction->handle(
                        userId: $this->userId,
                        permissionId: $permId,
                        skipTriggers: false,
                        executeTriggersSync: true,
                        resourceId: $entry['resourceId'],
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to revoke permission in batch', [
                        'user_id' => $this->userId,
                        'permission_id' => $permId,
                        'resource_id' => $entry['resourceId'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * @return array<int, array{permissionId: int, resourceId: ?int}>
     */
    private function normalize(): array
    {
        $result = [];
        foreach ($this->permissionIds as $entry) {
            if (is_array($entry)) {
                $result[] = [
                    'permissionId' => (int) $entry['permissionId'],
                    'resourceId' => isset($entry['resourceId']) ? (int) $entry['resourceId'] : null,
                ];
            } else {
                $result[] = [
                    'permissionId' => (int) $entry,
                    'resourceId' => null,
                ];
            }
        }
        return $result;
    }
}
