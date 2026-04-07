<?php

namespace ArcheeNic\PermissionRegistry\Events;

readonly class BeforePermissionGranted
{
    public function __construct(
        public int $userId,
        public int $permissionId,
        public string $permissionName,
        public string $service,
        public array $fieldValues = []
    ) {
    }
}
