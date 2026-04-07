<?php

namespace ArcheeNic\PermissionRegistry\Enums;

enum ApprovalType: string
{
    case SINGLE = 'single';
    case ALL = 'all';
    case N_OF_M = 'n_of_m';

    public function label(): string
    {
        return match ($this) {
            self::SINGLE => __('permission-registry::approvals.type.single'),
            self::ALL => __('permission-registry::approvals.type.all'),
            self::N_OF_M => __('permission-registry::approvals.type.n_of_m'),
        };
    }
}
