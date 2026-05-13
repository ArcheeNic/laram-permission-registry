<?php

namespace ArcheeNic\PermissionRegistry\Commands;

use ArcheeNic\PermissionRegistry\Enums\PermissionScope;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionResource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateToResourceModelCommand extends Command
{
    protected $signature = 'permission-registry:migrate-to-resource-model
                            {--dry-run : Only print the plan, do not modify data}
                            {--config= : Override config key (default: permission-registry-migration)}';

    protected $description = 'Idempotent migration: split legacy permissions into actions + resources, remap granted_permissions';

    public function handle(): int
    {
        $configKey = $this->option('config') ?: 'permission-registry-migration';
        $config = config($configKey);

        if (!is_array($config)) {
            $this->error("Migration config not found at [$configKey]");
            return self::FAILURE;
        }

        $actions = $config['actions'] ?? [];
        $mappings = $config['permission_mappings'] ?? [];

        if ($actions === [] || $mappings === []) {
            $this->error('Migration config must contain non-empty "actions" and "permission_mappings"');
            return self::FAILURE;
        }

        $isDry = (bool) $this->option('dry-run');
        $this->info($isDry ? 'DRY RUN — no changes will be persisted.' : 'APPLY MODE');

        try {
            DB::transaction(function () use ($actions, $mappings, $isDry) {
                $actionMap = $this->ensureActions($actions, $isDry);
                $resourceMap = $this->ensureResources($mappings, $isDry);
                $this->remapGrants($mappings, $actionMap, $resourceMap, $isDry);
                $this->softDeleteLegacyPermissions($mappings, $actionMap, $isDry);

                if ($isDry) {
                    throw new DryRunRollback();
                }
            });
        } catch (DryRunRollback) {
            $this->warn('Dry-run completed, transaction rolled back.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, int> map "service|name" => permission_id
     */
    private function ensureActions(array $actions, bool $isDry): array
    {
        $map = [];
        foreach ($actions as $row) {
            $service = (string) $row['service'];
            $name = (string) $row['name'];
            $scope = PermissionScope::from($row['scope'] ?? 'service');

            $existing = Permission::withTrashed()
                ->where('service', $service)
                ->where('name', $name)
                ->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update([
                    Permission::SCOPE => $scope->value,
                    Permission::DISPLAY_NAME => $row['display_name'] ?? $existing->display_name,
                    Permission::RESOURCE_KIND => $row['resource_kind'] ?? $existing->resource_kind,
                    Permission::DESCRIPTION => $row['description'] ?? $existing->description,
                ]);
                $action = $existing;
                $this->line(sprintf('  action exists: %s/%s [id=%d]', $service, $name, $action->id));
            } else {
                $action = Permission::create([
                    Permission::SERVICE => $service,
                    Permission::NAME => $name,
                    Permission::SCOPE => $scope->value,
                    Permission::DISPLAY_NAME => $row['display_name'] ?? null,
                    Permission::RESOURCE_KIND => $row['resource_kind'] ?? null,
                    Permission::DESCRIPTION => $row['description'] ?? null,
                ]);
                $this->line(sprintf('  action created: %s/%s [id=%d]', $service, $name, $action->id));
            }

            $map[$this->actionKey($service, $name)] = $action->id;
        }
        return $map;
    }

    /**
     * @return array<int, ?int> legacy permission_id => permission_resource_id (or null for service-scope)
     */
    private function ensureResources(array $mappings, bool $isDry): array
    {
        $map = [];
        foreach ($mappings as $legacyId => $target) {
            $resource = $target['resource'] ?? null;
            if ($resource === null) {
                $map[(int) $legacyId] = null;
                continue;
            }

            $service = (string) $resource['service'];
            $kind = (string) $resource['kind'];
            $externalId = (string) $resource['external_id'];
            $name = (string) $resource['name'];

            $existing = PermissionResource::findByExternal($service, $kind, $externalId);
            if ($existing) {
                $existing->update([
                    PermissionResource::NAME => $name,
                    PermissionResource::PRESENT_IN_SOURCE => true,
                ]);
                $resourceRow = $existing;
                $this->line(sprintf('  resource exists: %s/%s/%s [id=%d]', $service, $kind, $externalId, $resourceRow->id));
            } else {
                $resourceRow = PermissionResource::create([
                    PermissionResource::SERVICE => $service,
                    PermissionResource::KIND => $kind,
                    PermissionResource::EXTERNAL_ID => $externalId,
                    PermissionResource::NAME => $name,
                    PermissionResource::PRESENT_IN_SOURCE => true,
                ]);
                $this->line(sprintf('  resource created: %s/%s/%s [id=%d]', $service, $kind, $externalId, $resourceRow->id));
            }

            $map[(int) $legacyId] = $resourceRow->id;
        }
        return $map;
    }

    private function remapGrants(array $mappings, array $actionMap, array $resourceMap, bool $isDry): void
    {
        foreach ($mappings as $legacyId => $target) {
            $legacyId = (int) $legacyId;
            $actionKey = $this->actionKey($target['service'] ?? '', $target['action'] ?? '');
            $actionId = $actionMap[$actionKey] ?? null;

            if ($actionId === null) {
                $this->warn(sprintf('  legacy permission %d: action %s not found in actionMap, skipping', $legacyId, $actionKey));
                continue;
            }

            $resourceId = $resourceMap[$legacyId] ?? null;
            $resourceName = $target['resource']['name'] ?? null;

            $affected = DB::table('granted_permissions')
                ->where('permission_id', $legacyId)
                ->update([
                    'permission_id' => $actionId,
                    'resource_id' => $resourceId,
                    'resource_name_at_grant' => $resourceName,
                    'updated_at' => now(),
                ]);

            $this->line(sprintf(
                '  remap grants: legacy=%d -> action=%d resource=%s affected=%d',
                $legacyId,
                $actionId,
                $resourceId === null ? 'NULL' : (string) $resourceId,
                $affected
            ));
        }
    }

    private function softDeleteLegacyPermissions(array $mappings, array $actionMap, bool $isDry): void
    {
        $newActionIds = array_values($actionMap);
        foreach ($mappings as $legacyId => $target) {
            $legacyId = (int) $legacyId;
            if (in_array($legacyId, $newActionIds, true)) {
                continue;
            }
            $permission = Permission::find($legacyId);
            if ($permission && !$permission->trashed()) {
                $permission->delete();
                $this->line(sprintf('  legacy permission %d soft-deleted', $legacyId));
            }
        }
    }

    private function actionKey(string $service, string $name): string
    {
        return $service.'|'.$name;
    }
}

class DryRunRollback extends \RuntimeException
{
}
