<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\GrantedPermissionStatus;
use ArcheeNic\PermissionRegistry\Enums\ManagementMode;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Events\BeforePermissionGranted;
use ArcheeNic\PermissionRegistry\Exceptions\UserDeactivatedException;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use ArcheeNic\PermissionRegistry\Services\PermissionDependencyResolver;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

class GrantPermissionAction
{
    public function __construct(
        private PermissionDependencyResolver $dependencyResolver,
        private CheckApprovalRequiredAction $checkApproval,
        private CreateApprovalRequestAction $createApprovalRequest,
        private SavePermissionFieldsAction $saveFields,
        private ExecuteGrantTriggersAction $executeTriggers,
        private CreateManualProvisionTaskAction $createManualTask,
        private ScheduleAccessAttestationAction $scheduleAttestation,
        private AuditLogger $auditLogger
    ) {}

    public function handle(
        int $userId,
        int $permissionId,
        array $fieldValues = [],
        array $meta = [],
        ?string $expiresAt = null,
        bool $skipTriggers = false,
        bool $executeTriggersSync = false,
        ?int $requestedBy = null,
        ?int $confirmedBy = null,
        bool $skipApprovalCheck = false
    ): GrantedPermission {
        $virtualUser = VirtualUser::findOrFail($userId);
        if ($virtualUser->status === VirtualUserStatus::DEACTIVATED) {
            throw UserDeactivatedException::cannotGrantPermission($userId);
        }

        $permission = Permission::findOrFail($permissionId);

        $dependencyResult = $this->dependencyResolver->validatePermissionDependencies($userId, $permission, 'grant');
        if (! $dependencyResult->isValid) {
            throw ValidationException::withMessages([
                'dependencies' => [$dependencyResult->getErrorMessage()],
                'missing_permissions' => $dependencyResult->missingPermissions,
            ]);
        }

        $this->validateGlobalFields($userId, $permission, $fieldValues);

        if (! $skipApprovalCheck) {
            $policy = $this->checkApproval->handle($permissionId);
            if ($policy) {
                return $this->createAwaitingApproval(
                    $userId, $permission, $policy, $fieldValues, $meta, $expiresAt, $requestedBy
                );
            }
        }

        Event::dispatch(new BeforePermissionGranted(
            $userId, $permissionId, $permission->name, $permission->service, $fieldValues
        ));

        $mode = $permission->management_mode ?? ManagementMode::AUTOMATED;

        return match ($mode) {
            ManagementMode::MANUAL => $this->grantManual($userId, $permission, $fieldValues, $meta, $expiresAt, $requestedBy, $confirmedBy),
            ManagementMode::DECLARATIVE => $this->grantDeclarative($userId, $permission, $fieldValues, $meta, $expiresAt, $requestedBy, $confirmedBy),
            default => $this->grantAutomated($userId, $permission, $fieldValues, $meta, $expiresAt, $skipTriggers, $executeTriggersSync, $requestedBy, $confirmedBy),
        };
    }

    private function grantAutomated(
        int $userId,
        Permission $permission,
        array $fieldValues,
        array $meta,
        ?string $expiresAt,
        bool $skipTriggers,
        bool $executeTriggersSync,
        ?int $requestedBy,
        ?int $confirmedBy
    ): GrantedPermission {
        $status = $skipTriggers
            ? GrantedPermissionStatus::GRANTED->value
            : GrantedPermissionStatus::PENDING->value;

        $grantedPermission = GrantedPermission::updateOrCreate(
            [
                'virtual_user_id' => $userId,
                'permission_id' => $permission->id,
            ],
            [
                'status' => $status,
                'status_message' => null,
                'enabled' => true,
                'meta' => array_merge($meta, ['triggers_skipped' => $skipTriggers]),
                'granted_at' => now(),
                'expires_at' => $expiresAt,
                'requested_by' => $requestedBy,
                'confirmed_by' => $confirmedBy,
                'confirmed_at' => $confirmedBy ? now() : null,
            ]
        );

        $this->saveFields->handle($grantedPermission, $permission, $fieldValues);
        $this->executeTriggers->handle($grantedPermission, $permission, $skipTriggers, $executeTriggersSync);

        $this->auditLogger->log('permission.granted', $requestedBy ?? $confirmedBy, [
            'virtual_user_id' => $userId,
            'permission_id' => $permission->id,
            'permission_name' => $permission->name,
            'service' => $permission->service,
            'management_mode' => ManagementMode::AUTOMATED->value,
        ]);

        return $grantedPermission;
    }

    private function grantManual(
        int $userId,
        Permission $permission,
        array $fieldValues,
        array $meta,
        ?string $expiresAt,
        ?int $requestedBy,
        ?int $confirmedBy
    ): GrantedPermission {
        $grantedPermission = GrantedPermission::updateOrCreate(
            [
                'virtual_user_id' => $userId,
                'permission_id' => $permission->id,
            ],
            [
                'status' => GrantedPermissionStatus::MANUAL_PENDING->value,
                'status_message' => null,
                'enabled' => false,
                'meta' => $meta,
                'granted_at' => now(),
                'expires_at' => $expiresAt,
                'requested_by' => $requestedBy,
                'confirmed_by' => $confirmedBy,
                'confirmed_at' => null,
            ]
        );

        $this->saveFields->handle($grantedPermission, $permission, $fieldValues);
        $this->createManualTask->handle($grantedPermission, $permission);

        $this->auditLogger->log('permission.manual_pending', $requestedBy ?? $confirmedBy, [
            'virtual_user_id' => $userId,
            'permission_id' => $permission->id,
            'permission_name' => $permission->name,
            'service' => $permission->service,
            'management_mode' => ManagementMode::MANUAL->value,
        ]);

        return $grantedPermission;
    }

    private function grantDeclarative(
        int $userId,
        Permission $permission,
        array $fieldValues,
        array $meta,
        ?string $expiresAt,
        ?int $requestedBy,
        ?int $confirmedBy
    ): GrantedPermission {
        $grantedPermission = GrantedPermission::updateOrCreate(
            [
                'virtual_user_id' => $userId,
                'permission_id' => $permission->id,
            ],
            [
                'status' => GrantedPermissionStatus::DECLARED->value,
                'status_message' => null,
                'enabled' => true,
                'meta' => $meta,
                'granted_at' => now(),
                'expires_at' => $expiresAt,
                'requested_by' => $requestedBy,
                'confirmed_by' => $confirmedBy,
                'confirmed_at' => $confirmedBy ? now() : null,
            ]
        );

        $this->saveFields->handle($grantedPermission, $permission, $fieldValues);

        if ($permission->attestation_period_days) {
            $this->scheduleAttestation->handle($grantedPermission, $permission->attestation_period_days);
        }

        $this->auditLogger->log('permission.declared', $requestedBy ?? $confirmedBy, [
            'virtual_user_id' => $userId,
            'permission_id' => $permission->id,
            'permission_name' => $permission->name,
            'service' => $permission->service,
            'management_mode' => ManagementMode::DECLARATIVE->value,
        ]);

        return $grantedPermission;
    }

    private function createAwaitingApproval(
        int $userId,
        Permission $permission,
        \ArcheeNic\PermissionRegistry\Models\ApprovalPolicy $policy,
        array $fieldValues,
        array $meta,
        ?string $expiresAt,
        ?int $requestedBy
    ): GrantedPermission {
        $grantedPermission = GrantedPermission::updateOrCreate(
            [
                'virtual_user_id' => $userId,
                'permission_id' => $permission->id,
            ],
            [
                'status' => GrantedPermissionStatus::AWAITING_APPROVAL->value,
                'status_message' => null,
                'enabled' => false,
                'meta' => $meta,
                'granted_at' => now(),
                'expires_at' => $expiresAt,
                'requested_by' => $requestedBy,
                'confirmed_by' => null,
                'confirmed_at' => null,
            ]
        );

        $this->saveFields->handle($grantedPermission, $permission, $fieldValues);
        $this->createApprovalRequest->handle($grantedPermission, $policy, $requestedBy);

        return $grantedPermission;
    }

    private function validateGlobalFields(int $userId, Permission $permission, array $fieldValues): void
    {
        $globalFields = $permission->fields()->where(PermissionField::IS_GLOBAL, true)->get();

        if ($globalFields->isEmpty()) {
            return;
        }

        $missingFields = [];

        foreach ($globalFields as $field) {
            if (isset($fieldValues[$field->id]) && ! empty($fieldValues[$field->id])) {
                continue;
            }

            $existingValue = VirtualUserFieldValue::where(VirtualUserFieldValue::VIRTUAL_USER_ID, $userId)
                ->where(VirtualUserFieldValue::PERMISSION_FIELD_ID, $field->id)
                ->value(VirtualUserFieldValue::VALUE);

            if (empty($existingValue)) {
                $missingFields[] = [
                    'id' => $field->id,
                    'name' => $field->name,
                ];
            }
        }

        if (! empty($missingFields)) {
            throw ValidationException::withMessages([
                'global_fields' => [__('permission-registry::Fill Required Global Fields')],
                'missing_fields' => $missingFields,
            ]);
        }
    }
}
