<?php

namespace ArcheeNic\PermissionRegistry\Enums;

enum ApproverType: string
{
    case VIRTUAL_USER = 'virtual_user';
    case POSITION = 'position';

    public function label(): string
    {
        return match ($this) {
            self::VIRTUAL_USER => __('permission-registry::approvals.approver_type.virtual_user'),
            self::POSITION => __('permission-registry::approvals.approver_type.position'),
        };
    }
}
