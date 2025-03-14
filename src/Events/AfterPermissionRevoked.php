<?php

namespace ArcheeNic\PermissionRegistry\Events;

readonly class AfterPermissionRevoked
{
    public function __construct(
        public int $userId,
        public int $permissionId,
        public string $permissionName,
        public string $service
    ) {
    }
}
