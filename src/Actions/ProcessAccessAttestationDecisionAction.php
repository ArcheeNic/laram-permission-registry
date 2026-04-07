<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\AttestationStatus;
use ArcheeNic\PermissionRegistry\Models\AccessAttestation;
use Illuminate\Validation\ValidationException;

class ProcessAccessAttestationDecisionAction
{
    private const MAX_COMMENT_LENGTH = 2000;

    public function __construct(
        private RevokePermissionAction $revokeAction,
        private ScheduleAccessAttestationAction $scheduleAction,
        private AuditLogger $auditLogger
    ) {}

    public function handle(
        AccessAttestation $attestation,
        AttestationStatus $decision,
        int $decidedBy,
        ?string $comment = null
    ): AccessAttestation {
        if ($attestation->status->isTerminal()) {
            throw ValidationException::withMessages([
                'attestation' => [__('permission-registry::governance.attestation_already_decided')],
            ]);
        }

        if ($comment !== null && strlen($comment) > self::MAX_COMMENT_LENGTH) {
            throw ValidationException::withMessages([
                'comment' => [__('permission-registry::governance.comment_too_long', ['max' => self::MAX_COMMENT_LENGTH])],
            ]);
        }

        $attestation->update([
            AccessAttestation::STATUS => $decision->value,
            AccessAttestation::DECIDED_BY => $decidedBy,
            AccessAttestation::DECIDED_AT => now(),
            AccessAttestation::COMMENT => $comment,
        ]);

        $grantedPermission = $attestation->grantedPermission;

        if ($decision === AttestationStatus::CONFIRMED) {
            $this->scheduleAction->handle($grantedPermission);
        }

        if ($decision === AttestationStatus::REJECTED) {
            $this->revokeAction->handle(
                $grantedPermission->virtual_user_id,
                $grantedPermission->permission_id,
                skipTriggers: true
            );
        }

        $this->auditLogger->log('attestation.decided', $decidedBy, [
            'attestation_id' => $attestation->id,
            'decision' => $decision->value,
            'granted_permission_id' => $grantedPermission->id,
        ]);

        return $attestation;
    }
}
