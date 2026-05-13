<?php

namespace ArcheeNic\PermissionRegistry\Commands;

use ArcheeNic\PermissionRegistry\Actions\SyncResourcesAction;
use ArcheeNic\PermissionRegistry\Services\ResourceSyncerRegistry;
use Illuminate\Console\Command;

class SyncResourcesCommand extends Command
{
    protected $signature = 'permission-registry:sync-resources
        {service? : Service name; omit to sync all registered services}
        {--only=* : External IDs to sync selectively (no disappearance marking)}';

    protected $description = 'Discover resources from external integrations into permission_resources catalog';

    public function handle(ResourceSyncerRegistry $registry, SyncResourcesAction $action): int
    {
        $service = $this->argument('service');
        $only = array_values(array_filter(array_map('strval', (array) $this->option('only'))));

        $syncers = $service === null
            ? $registry->all()
            : $registry->forService($service);

        if ($syncers === []) {
            $this->warn($service === null
                ? 'No resource syncers registered.'
                : "No resource syncers registered for service [$service].");

            return self::SUCCESS;
        }

        $isSelective = $only !== [];
        if ($isSelective) {
            $this->info('Selective mode: '.implode(', ', $only));
        }

        foreach ($syncers as $syncer) {
            $label = $syncer->service().'/'.$syncer->kind();
            $this->info("Syncing $label …");
            try {
                $report = $isSelective
                    ? $action->handleSelective($syncer, $only)
                    : $action->handle($syncer);
                $this->line(sprintf(
                    '  created=%d updated=%d reappeared=%d disappeared=%d errors=%d',
                    $report->created,
                    $report->updated,
                    $report->reappeared,
                    $report->disappeared,
                    count($report->errors),
                ));
                foreach ($report->errors as $error) {
                    $this->warn(sprintf(
                        '  ! external_id=%s message=%s',
                        $error['external_id'] ?? 'null',
                        $error['message'] ?? '',
                    ));
                }
            } catch (\Throwable $e) {
                $this->error("Syncer $label failed: ".$e->getMessage());

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
