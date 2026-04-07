<?php

namespace ArcheeNic\PermissionRegistry\Support;

use ArcheeNic\PermissionRegistry\Contracts\UserToVirtualUserResolver;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;

class DefaultUserToVirtualUserResolver implements UserToVirtualUserResolver
{
    public function resolve(int $userId): ?int
    {
        $virtualUser = VirtualUser::where('user_id', $userId)->first();

        return $virtualUser?->id;
    }
}
