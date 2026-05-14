<?php

namespace ArcheeNic\PermissionRegistry\Commands;

use ArcheeNic\PermissionRegistry\Actions\SyncResourcesAction;
use Illuminate\Console\Command;

class RegisterResourceCommand extends Command
{
    protected $signature = 'permission-registry:register-resource
        {service : Service code (e.g. b24, telegram, gsheet)}
        {kind : Resource kind (department, chat, sheet, ...)}
        {external_id : External resource identifier}
        {name : Human-readable name}
        {--meta=* : Optional metadata pairs key=value}';

    protected $description = 'Manually register a single resource into the catalog (used when discovery is unavailable).';

    public function handle(SyncResourcesAction $action): int
    {
        $service = (string) $this->argument('service');
        $kind = (string) $this->argument('kind');
        $externalId = (string) $this->argument('external_id');
        $name = (string) $this->argument('name');
        $metadata = $this->parseMeta((array) $this->option('meta'));

        $resource = $action->registerManually($service, $kind, $externalId, $name, $metadata);

        $this->info(sprintf(
            'Resource saved: id=%d service=%s kind=%s external_id=%s name=%s',
            $resource->id, $resource->service, $resource->kind, $resource->external_id, $resource->name
        ));

        return self::SUCCESS;
    }

    private function parseMeta(array $pairs): ?array
    {
        if ($pairs === []) {
            return null;
        }
        $result = [];
        foreach ($pairs as $pair) {
            if (! is_string($pair) || ! str_contains($pair, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $pair, 2);
            $result[trim($k)] = trim($v);
        }

        return $result === [] ? null : $result;
    }
}
