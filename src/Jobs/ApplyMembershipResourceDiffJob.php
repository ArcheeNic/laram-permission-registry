<?php

namespace ArcheeNic\PermissionRegistry\Jobs;

use ArcheeNic\PermissionRegistry\Actions\GrantPermissionAction;
use ArcheeNic\PermissionRegistry\Actions\RevokePermissionAction;
use ArcheeNic\PermissionRegistry\Services\UserAutoGrantPairsCollector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ApplyMembershipResourceDiffJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    /**
     * @param  'group'|'position'  $contextType
     * @param  array<int, array{permission_id:int, resource_id:?int, auto_grant?:bool, auto_revoke?:bool}>  $addedPairs
     * @param  array<int, array{permission_id:int, resource_id:?int, auto_grant?:bool, auto_revoke?:bool}>  $removedPairs
     * @param  array<int, int>  $userIds
     */
    public function __construct(
        public string $contextType,
        public int $contextId,
        public array $addedPairs,
        public array $removedPairs,
        public array $userIds,
    ) {}

    public function handle(
        GrantPermissionAction $grantAction,
        RevokePermissionAction $revokeAction,
        UserAutoGrantPairsCollector $pairsCollector,
    ): void {
        if (empty($this->userIds) || (empty($this->addedPairs) && empty($this->removedPairs))) {
            return;
        }

        $source = $this->contextType;
        $excludeGroupId = $this->contextType === 'group' ? $this->contextId : null;
        $excludePositionId = $this->contextType === 'position' ? $this->contextId : null;

        foreach ($this->userIds as $userId) {
            foreach ($this->addedPairs as $pair) {
                if (empty($pair['auto_grant'])) {
                    continue;
                }
                try {
                    $grantAction->handle(
                        userId: $userId,
                        permissionId: $pair['permission_id'],
                        meta: ['auto_granted' => true, 'auto_grant_source' => $source],
                        resourceId: $pair['resource_id'],
                    );
                } catch (\Throwable $e) {
                    Log::warning(sprintf(
                        '[%s:%d] Failed to grant permission %d (resource=%s) to user %d on membership diff: %s',
                        $this->contextType,
                        $this->contextId,
                        $pair['permission_id'],
                        $pair['resource_id'] ?? 'null',
                        $userId,
                        $e->getMessage(),
                    ));
                }
            }

            $remainingPairs = $pairsCollector->collect(
                $userId,
                excludeGroupId: $excludeGroupId,
                excludePositionId: $excludePositionId,
            );

            foreach ($this->removedPairs as $pair) {
                if (empty($pair['auto_revoke'])) {
                    continue;
                }
                $key = UserAutoGrantPairsCollector::key($pair['permission_id'], $pair['resource_id']);
                if (isset($remainingPairs[$key])) {
                    continue;
                }
                try {
                    $revokeAction->handle(
                        userId: $userId,
                        permissionId: $pair['permission_id'],
                        skipTriggers: false,
                        resourceId: $pair['resource_id'],
                    );
                } catch (\Throwable $e) {
                    Log::warning(sprintf(
                        '[%s:%d] Failed to revoke permission %d (resource=%s) from user %d on membership diff: %s',
                        $this->contextType,
                        $this->contextId,
                        $pair['permission_id'],
                        $pair['resource_id'] ?? 'null',
                        $userId,
                        $e->getMessage(),
                    ));
                }
            }
        }
    }
}
