<?php

namespace App\Modules\PermissionRegistry\Events;

readonly class AfterPermissionGranted
{
    public function __construct(
        public int $userId,
        public int $permissionId,
        public string $permissionName,
        public string $service
    ) {
    }
}
