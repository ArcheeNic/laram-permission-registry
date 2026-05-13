<?php

namespace ArcheeNic\PermissionRegistry\Contracts;

use ArcheeNic\PermissionRegistry\ValueObjects\SyncReport;

interface ResourceSyncerInterface
{
    public function service(): string;

    public function kind(): string;

    /**
     * @return iterable<array{external_id: string, name: string, metadata?: array}>
     */
    public function fetch(): iterable;

    /**
     * @param  string[]  $externalIds
     * @return iterable<array{external_id: string, name: string, metadata?: array}>
     */
    public function fetchByIds(array $externalIds): iterable;

    public function sync(): SyncReport;
}
