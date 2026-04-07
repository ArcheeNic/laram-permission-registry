<?php

namespace ArcheeNic\PermissionRegistry\Enums;

enum ManagementMode: string
{
    case AUTOMATED = 'automated';
    case MANUAL = 'manual';
    case DECLARATIVE = 'declarative';

    public function label(): string
    {
        return match ($this) {
            self::AUTOMATED => __('permission-registry::governance.management_mode.automated'),
            self::MANUAL => __('permission-registry::governance.management_mode.manual'),
            self::DECLARATIVE => __('permission-registry::governance.management_mode.declarative'),
        };
    }

    public function requiresTriggers(): bool
    {
        return $this === self::AUTOMATED;
    }

    public function requiresManualTask(): bool
    {
        return $this === self::MANUAL;
    }

    public function requiresAttestation(): bool
    {
        return $this === self::DECLARATIVE;
    }
}
