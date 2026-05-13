<?php

namespace ArcheeNic\PermissionRegistry\Enums;

enum PermissionScope: string
{
    case Service = 'service';
    case Resource = 'resource';

    public function label(): string
    {
        return match ($this) {
            self::Service => __('permission-registry::Service-wide'),
            self::Resource => __('permission-registry::Resource-scoped'),
        };
    }
}
