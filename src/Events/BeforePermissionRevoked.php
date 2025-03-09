<?php

namespace ArcheeNic\PermissionRegistry\Events;

readonly class BeforePermissionRevoked
{
    public function __construct(
        public int $userId,
        public int $permissionId,
        public string $permissionName,
        public string $service
    ) {
    }
}
