<?php

namespace ArcheeNic\PermissionRegistry\Enums;

enum GrantedPermissionStatus: string
{
    case AWAITING_APPROVAL = 'awaiting_approval';
    case PENDING = 'pending';
    case GRANTING = 'granting';
    case GRANTED = 'granted';
    case REVOKING = 'revoking';
    case REVOKED = 'revoked';
    case FAILED = 'failed';
    case PARTIALLY_GRANTED = 'partially_granted';
    case PARTIALLY_REVOKED = 'partially_revoked';
    case REJECTED = 'rejected';
    case MANUAL_PENDING = 'manual_pending';
    case DECLARED = 'declared';

    public function label(): string
    {
        return match ($this) {
            self::AWAITING_APPROVAL => __('permission-registry::approvals.granted_status.awaiting_approval'),
            self::PENDING => __('permission-registry::approvals.granted_status.pending'),
            self::GRANTING => __('permission-registry::approvals.granted_status.granting'),
            self::GRANTED => __('permission-registry::approvals.granted_status.granted'),
            self::REVOKING => __('permission-registry::approvals.granted_status.revoking'),
            self::REVOKED => __('permission-registry::approvals.granted_status.revoked'),
            self::FAILED => __('permission-registry::approvals.granted_status.failed'),
            self::PARTIALLY_GRANTED => __('permission-registry::approvals.granted_status.partially_granted'),
            self::PARTIALLY_REVOKED => __('permission-registry::approvals.granted_status.partially_revoked'),
            self::REJECTED => __('permission-registry::approvals.granted_status.rejected'),
            self::MANUAL_PENDING => __('permission-registry::approvals.granted_status.manual_pending'),
            self::DECLARED => __('permission-registry::approvals.granted_status.declared'),
        };
    }

    public function isInProgress(): bool
    {
        return in_array($this, [self::PENDING, self::GRANTING, self::REVOKING, self::MANUAL_PENDING]);
    }

    public function isCompleted(): bool
    {
        return in_array($this, [self::GRANTED, self::REVOKED]);
    }

    public function hasError(): bool
    {
        return in_array($this, [self::FAILED, self::PARTIALLY_GRANTED, self::PARTIALLY_REVOKED]);
    }

    public function isAwaitingApproval(): bool
    {
        return $this === self::AWAITING_APPROVAL;
    }

    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }
}
