<?php

namespace ArcheeNic\PermissionRegistry\Events;

readonly class UserPositionChanged
{
    public function __construct(
        public int $userId,
        public int $positionId,
        public ?int $oldPositionId = null
    ) {
    }
}
