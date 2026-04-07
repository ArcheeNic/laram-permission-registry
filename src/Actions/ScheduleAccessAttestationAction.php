<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\AttestationStatus;
use ArcheeNic\PermissionRegistry\Models\AccessAttestation;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;

class ScheduleAccessAttestationAction
{
    public function handle(GrantedPermission $grantedPermission, ?int $periodDays = null): ?AccessAttestation
    {
        $permission = $grantedPermission->permission ?? Permission::find($grantedPermission->permission_id);

        $days = $periodDays ?? $permission->attestation_period_days;

        if (! $days) {
            return null;
        }

        return AccessAttestation::create([
            AccessAttestation::GRANTED_PERMISSION_ID => $grantedPermission->id,
            AccessAttestation::ATTESTATION_PERIOD_DAYS => $days,
            AccessAttestation::DUE_AT => now()->addDays($days),
            AccessAttestation::STATUS => AttestationStatus::PENDING->value,
        ]);
    }
}
