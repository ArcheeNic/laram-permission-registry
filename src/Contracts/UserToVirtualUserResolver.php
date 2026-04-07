<?php

namespace ArcheeNic\PermissionRegistry\Contracts;

interface UserToVirtualUserResolver
{
    /**
     * Resolve application user id (users.id) to virtual user id (virtual_users.id).
     */
    public function resolve(int $userId): ?int;
}
