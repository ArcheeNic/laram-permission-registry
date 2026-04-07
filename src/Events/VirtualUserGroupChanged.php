<?php

namespace ArcheeNic\PermissionRegistry\Events;

readonly class VirtualUserGroupChanged
{
    public function __construct(
        public int $userId,
        public int $groupId,
        public bool $added
    ) {
    }
}
