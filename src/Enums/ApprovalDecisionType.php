<?php

namespace ArcheeNic\PermissionRegistry\Enums;

enum ApprovalDecisionType: string
{
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::APPROVED => __('permission-registry::approvals.decision.approved'),
            self::REJECTED => __('permission-registry::approvals.decision.rejected'),
        };
    }
}
