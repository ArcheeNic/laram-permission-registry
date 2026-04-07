<?php

namespace ArcheeNic\PermissionRegistry\Enums;

enum ImportMatchStatus: string
{
    case NEW = 'new';
    case EXISTS = 'exists';
    case CHANGED = 'changed';
    case MISSING = 'missing';
}
