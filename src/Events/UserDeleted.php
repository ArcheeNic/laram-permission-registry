<?php

namespace App\Modules\PermissionRegistry\Events;

readonly class UserDeleted
{
    public function __construct(
        public int $userId,
        public string $email
    ) {
    }
}
