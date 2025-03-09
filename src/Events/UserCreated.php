<?php

namespace App\Modules\PermissionRegistry\Events;

readonly class UserCreated
{
    public function __construct(
        public int $userId,
        public string $email
    ) {
    }
}
