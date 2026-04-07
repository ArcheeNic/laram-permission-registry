<?php

namespace ArcheeNic\PermissionRegistry\Enums;

enum EmailDuplicateResolutionStrategy: string
{
    case AUTO_INCREMENT = 'auto_increment';
    case MANUAL = 'manual';
}
