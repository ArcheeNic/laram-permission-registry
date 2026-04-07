<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Enums\AttestationStatus;
use ArcheeNic\PermissionRegistry\Models\Base\AccessAttestation as BaseAccessAttestation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessAttestation extends BaseAccessAttestation
{
    protected $casts = [
        self::STATUS => AttestationStatus::class,
        self::DUE_AT => 'datetime',
        self::DECIDED_AT => 'datetime',
        self::ATTESTATION_PERIOD_DAYS => 'integer',
    ];

    public function grantedPermission(): BelongsTo
    {
        return $this->belongsTo(GrantedPermission::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(VirtualUser::class, 'decided_by');
    }
}
