<?php

namespace ArcheeNic\PermissionRegistry\Enums;

enum ApprovalRequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('permission-registry::approvals.status.pending'),
            self::APPROVED => __('permission-registry::approvals.status.approved'),
            self::REJECTED => __('permission-registry::approvals.status.rejected'),
            self::EXPIRED => __('permission-registry::approvals.status.expired'),
            self::CANCELLED => __('permission-registry::approvals.status.cancelled'),
        };
    }

    public function isResolved(): bool
    {
        return in_array($this, [self::APPROVED, self::REJECTED, self::EXPIRED, self::CANCELLED]);
    }
}
