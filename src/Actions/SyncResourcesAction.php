<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Contracts\ResourceSyncerInterface;
use ArcheeNic\PermissionRegistry\Models\PermissionResource;
use ArcheeNic\PermissionRegistry\ValueObjects\SyncReport;
use Illuminate\Support\Facades\DB;

class SyncResourcesAction
{
    public function handle(ResourceSyncerInterface $syncer): SyncReport
    {
        $service = $syncer->service();
        $kind = $syncer->kind();
        $now = now();

        $seenExternalIds = [];
        $created = 0;
        $updated = 0;
        $reappeared = 0;
        $errors = [];

        DB::transaction(function () use ($syncer, $service, $kind, $now, &$seenExternalIds, &$created, &$updated, &$reappeared, &$errors) {
            foreach ($syncer->fetch() as $row) {
                $result = $this->upsertRow($service, $kind, $row, $now, $errors);
                if ($result === null) {
                    continue;
                }
                $seenExternalIds[] = $result['external_id'];
                if ($result['created']) {
                    $created++;
                } else {
                    $updated++;
                    if ($result['reappeared']) {
                        $reappeared++;
                    }
                }
            }

            PermissionResource::query()
                ->where('service', $service)
                ->where('kind', $kind)
                ->where('present_in_source', true)
                ->whereNotIn('external_id', $seenExternalIds)
                ->update([
                    'present_in_source' => false,
                    'synced_at' => $now,
                ]);
        });

        $disappeared = PermissionResource::query()
            ->where('service', $service)
            ->where('kind', $kind)
            ->where('present_in_source', false)
            ->whereDate('synced_at', $now->toDateString())
            ->count();

        return new SyncReport(
            service: $service,
            kind: $kind,
            created: $created,
            updated: $updated,
            disappeared: $disappeared,
            reappeared: $reappeared,
            errors: $errors,
        );
    }

    /**
     * Избирательная синхронизация — обновляет только переданные external_id,
     * не помечая остальные ресурсы как «исчезнувшие».
     *
     * @param  string[]  $externalIds
     */
    public function handleSelective(ResourceSyncerInterface $syncer, array $externalIds): SyncReport
    {
        $service = $syncer->service();
        $kind = $syncer->kind();
        $now = now();

        $created = 0;
        $updated = 0;
        $reappeared = 0;
        $errors = [];

        DB::transaction(function () use ($syncer, $service, $kind, $now, $externalIds, &$created, &$updated, &$reappeared, &$errors) {
            foreach ($syncer->fetchByIds($externalIds) as $row) {
                $result = $this->upsertRow($service, $kind, $row, $now, $errors);
                if ($result === null) {
                    continue;
                }
                if ($result['created']) {
                    $created++;
                } else {
                    $updated++;
                    if ($result['reappeared']) {
                        $reappeared++;
                    }
                }
            }
        });

        return new SyncReport(
            service: $service,
            kind: $kind,
            created: $created,
            updated: $updated,
            disappeared: 0,
            reappeared: $reappeared,
            errors: $errors,
        );
    }

    /**
     * Ручная регистрация одного ресурса (когда discovery невозможен).
     */
    public function registerManually(string $service, string $kind, string $externalId, string $name, ?array $metadata = null): PermissionResource
    {
        $now = now();
        $existing = PermissionResource::withTrashed()
            ->where('service', $service)
            ->where('kind', $kind)
            ->where('external_id', $externalId)
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            $existing->fill([
                PermissionResource::NAME => $name,
                PermissionResource::METADATA => $metadata,
                PermissionResource::SYNCED_AT => $now,
                PermissionResource::PRESENT_IN_SOURCE => true,
            ])->save();

            return $existing;
        }

        return PermissionResource::query()->create([
            PermissionResource::SERVICE => $service,
            PermissionResource::KIND => $kind,
            PermissionResource::EXTERNAL_ID => $externalId,
            PermissionResource::NAME => $name,
            PermissionResource::METADATA => $metadata,
            PermissionResource::SYNCED_AT => $now,
            PermissionResource::PRESENT_IN_SOURCE => true,
        ]);
    }

    /**
     * @return array{external_id:string, created:bool, reappeared:bool}|null
     */
    private function upsertRow(string $service, string $kind, mixed $row, \DateTimeInterface $now, array &$errors): ?array
    {
        try {
            $externalId = (string) $row['external_id'];
            $name = (string) $row['name'];
            $metadata = $row['metadata'] ?? null;

            $existing = PermissionResource::query()
                ->where('service', $service)
                ->where('kind', $kind)
                ->where('external_id', $externalId)
                ->first();

            if ($existing === null) {
                PermissionResource::query()->create([
                    PermissionResource::SERVICE => $service,
                    PermissionResource::KIND => $kind,
                    PermissionResource::EXTERNAL_ID => $externalId,
                    PermissionResource::NAME => $name,
                    PermissionResource::METADATA => $metadata,
                    PermissionResource::SYNCED_AT => $now,
                    PermissionResource::PRESENT_IN_SOURCE => true,
                ]);

                return ['external_id' => $externalId, 'created' => true, 'reappeared' => false];
            }

            $wasMissing = ! $existing->present_in_source;
            $existing->name = $name;
            $existing->metadata = $metadata;
            $existing->synced_at = $now;
            $existing->present_in_source = true;
            $existing->save();

            return ['external_id' => $externalId, 'created' => false, 'reappeared' => $wasMissing];
        } catch (\Throwable $e) {
            $errors[] = [
                'external_id' => $row['external_id'] ?? null,
                'message' => $e->getMessage(),
            ];

            return null;
        }
    }
}
