<?php

namespace ArcheeNic\PermissionRegistry\ValueObjects;

use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;

readonly class TriggerContext
{
    public function __construct(
        public int $virtualUserId,
        public Permission $permission,
        public int $permissionTriggerId,
        public array $fieldValues,
        public array $globalFields,
        public ?GrantedPermission $grantedPermission = null,
        public array $config = []
    ) {
    }
}
