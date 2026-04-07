<?php

namespace ArcheeNic\PermissionRegistry\Enums;

enum RiskLevel: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::LOW => __('permission-registry::governance.risk_level.low'),
            self::MEDIUM => __('permission-registry::governance.risk_level.medium'),
            self::HIGH => __('permission-registry::governance.risk_level.high'),
            self::CRITICAL => __('permission-registry::governance.risk_level.critical'),
        };
    }
}
