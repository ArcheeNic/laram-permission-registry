<?php

namespace ArcheeNic\PermissionRegistry\Enums;

enum AttestationStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('permission-registry::governance.attestation_status.pending'),
            self::CONFIRMED => __('permission-registry::governance.attestation_status.confirmed'),
            self::REJECTED => __('permission-registry::governance.attestation_status.rejected'),
            self::EXPIRED => __('permission-registry::governance.attestation_status.expired'),
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::CONFIRMED, self::REJECTED, self::EXPIRED]);
    }
}
