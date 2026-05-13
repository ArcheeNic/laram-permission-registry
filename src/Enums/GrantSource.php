<?php

namespace ArcheeNic\PermissionRegistry\Enums;

enum GrantSource: string
{
    case Manual = 'manual';
    case Trigger = 'trigger';
    case Discovery = 'discovery';
}
