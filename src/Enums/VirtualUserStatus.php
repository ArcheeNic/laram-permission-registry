<?php

namespace ArcheeNic\PermissionRegistry\Enums;

enum VirtualUserStatus: string
{
    case ACTIVE = 'active';
    case DEACTIVATED = 'deactivated';
}
