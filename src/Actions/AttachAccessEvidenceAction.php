<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\EvidenceType;
use ArcheeNic\PermissionRegistry\Models\AccessEvidence;
use Illuminate\Validation\ValidationException;

class AttachAccessEvidenceAction
{
    private const MAX_VALUE_LENGTH = 2000;

    private const MAX_META_JSON_LENGTH = 10000;

    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    public function handle(
        int $grantedPermissionId,
        string $type,
        string $value,
        ?int $providedBy = null,
        ?int $manualProvisionTaskId = null,
        ?array $meta = null
    ): AccessEvidence {
        if (EvidenceType::tryFrom($type) === null) {
            throw ValidationException::withMessages([
                'type' => [__('permission-registry::governance.invalid_evidence_type')],
            ]);
        }

        if (mb_strlen($value) > self::MAX_VALUE_LENGTH) {
            throw ValidationException::withMessages([
                'value' => [__('permission-registry::governance.evidence_value_too_long', ['max' => self::MAX_VALUE_LENGTH])],
            ]);
        }

        if ($meta !== null) {
            $metaJson = json_encode($meta);
            if ($metaJson !== false && strlen($metaJson) > self::MAX_META_JSON_LENGTH) {
                throw ValidationException::withMessages([
                    'meta' => [__('permission-registry::governance.evidence_meta_too_large')],
                ]);
            }
        }

        $evidence = AccessEvidence::create([
            AccessEvidence::GRANTED_PERMISSION_ID => $grantedPermissionId,
            AccessEvidence::MANUAL_PROVISION_TASK_ID => $manualProvisionTaskId,
            AccessEvidence::TYPE => $type,
            AccessEvidence::VALUE => $value,
            AccessEvidence::META => $meta,
            AccessEvidence::PROVIDED_BY => $providedBy,
        ]);

        $this->auditLogger->log('evidence.attached', $providedBy, [
            'evidence_id' => $evidence->id,
            'granted_permission_id' => $grantedPermissionId,
            'type' => $type,
        ]);

        return $evidence;
    }
}
