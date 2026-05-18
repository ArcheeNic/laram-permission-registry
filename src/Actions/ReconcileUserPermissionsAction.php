<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Jobs\GrantMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Jobs\RevokeMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Services\UserAutoGrantPairsCollector;

class ReconcileUserPermissionsAction
{
    public function __construct(
        private UserAutoGrantPairsCollector $pairsCollector
    ) {}

    public function handle(int $userId): void
    {
        $targetPairs = $this->pairsCollector->collect($userId);

        $currentEnabledPairs = [];
        $currentAutoGrantedPairs = [];

        GrantedPermission::query()
            ->where(GrantedPermission::VIRTUAL_USER_ID, $userId)
            ->where(GrantedPermission::ENABLED, true)
            ->get([
                GrantedPermission::PERMISSION_ID,
                GrantedPermission::RESOURCE_ID,
                GrantedPermission::META,
            ])
            ->each(function (GrantedPermission $granted) use (&$currentEnabledPairs, &$currentAutoGrantedPairs) {
                $permissionId = (int) $granted->getAttribute(GrantedPermission::PERMISSION_ID);
                $rawResourceId = $granted->getAttribute(GrantedPermission::RESOURCE_ID);
                $resourceId = $rawResourceId !== null ? (int) $rawResourceId : null;
                $key = UserAutoGrantPairsCollector::key($permissionId, $resourceId);
                $pair = ['permission_id' => $permissionId, 'resource_id' => $resourceId];

                $currentEnabledPairs[$key] = $pair;

                $meta = $granted->getAttribute(GrantedPermission::META);
                if (is_array($meta) && (($meta['auto_granted'] ?? false) === true)) {
                    $currentAutoGrantedPairs[$key] = $pair;
                }
            });

        $pairsToGrant = [];
        foreach (array_keys($targetPairs) as $key) {
            if (isset($currentEnabledPairs[$key])) {
                continue;
            }
            $pairsToGrant[] = $this->splitKey($key);
        }

        $pairsToRevoke = [];
        foreach ($currentAutoGrantedPairs as $key => $pair) {
            if (isset($targetPairs[$key])) {
                continue;
            }
            $pairsToRevoke[] = $pair;
        }

        if (! empty($pairsToGrant)) {
            $permissionsData = array_map(static fn (array $pair): array => [
                'permissionId' => $pair['permission_id'],
                'resourceId' => $pair['resource_id'],
                'fieldValues' => [],
                'meta' => [
                    'auto_granted' => true,
                    'auto_grant_source' => 'reconcile',
                ],
                'expiresAt' => null,
            ], $pairsToGrant);

            GrantMultiplePermissionsJob::dispatch($userId, $permissionsData);
        }

        if (! empty($pairsToRevoke)) {
            $revokeData = array_map(static fn (array $pair): array => [
                'permissionId' => $pair['permission_id'],
                'resourceId' => $pair['resource_id'],
            ], $pairsToRevoke);

            RevokeMultiplePermissionsJob::dispatch($userId, $revokeData);
        }
    }

    /**
     * @return array{permission_id:int, resource_id:?int}
     */
    private function splitKey(string $key): array
    {
        [$permissionPart, $resourcePart] = explode('|', $key, 2) + [null, ''];

        return [
            'permission_id' => (int) $permissionPart,
            'resource_id' => $resourcePart === '' ? null : (int) $resourcePart,
        ];
    }
}
