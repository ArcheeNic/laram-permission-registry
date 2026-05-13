<?php

namespace ArcheeNic\PermissionRegistry\Services;

use ArcheeNic\PermissionRegistry\Contracts\ResourceSyncerInterface;

class ResourceSyncerRegistry
{
    /** @var array<string, ResourceSyncerInterface[]> */
    private array $syncersByService = [];

    public function register(ResourceSyncerInterface $syncer): void
    {
        $this->syncersByService[$syncer->service()][] = $syncer;
    }

    /**
     * @return ResourceSyncerInterface[]
     */
    public function forService(string $service): array
    {
        return $this->syncersByService[$service] ?? [];
    }

    /**
     * @return ResourceSyncerInterface[]
     */
    public function all(): array
    {
        return array_merge(...array_values($this->syncersByService)) ?: [];
    }

    /**
     * @return string[]
     */
    public function services(): array
    {
        return array_keys($this->syncersByService);
    }
}
