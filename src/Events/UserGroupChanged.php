<?php

namespace App\Modules\PermissionRegistry\Events;

readonly class UserGroupChanged
{
    public function __construct(
        public int $userId,
        public int $groupId,
        public bool $added
    ) {
    }
}
