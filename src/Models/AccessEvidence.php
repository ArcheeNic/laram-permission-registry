<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Enums\EvidenceType;
use ArcheeNic\PermissionRegistry\Models\Base\AccessEvidence as BaseAccessEvidence;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessEvidence extends BaseAccessEvidence
{
    protected $casts = [
        self::TYPE => EvidenceType::class,
        self::META => 'array',
    ];

    public function grantedPermission(): BelongsTo
    {
        return $this->belongsTo(GrantedPermission::class);
    }

    public function manualProvisionTask(): BelongsTo
    {
        return $this->belongsTo(ManualProvisionTask::class);
    }
}
