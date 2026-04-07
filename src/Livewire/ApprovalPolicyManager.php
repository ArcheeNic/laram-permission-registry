<?php

namespace ArcheeNic\PermissionRegistry\Livewire;

use ArcheeNic\PermissionRegistry\Enums\ApprovalType;
use ArcheeNic\PermissionRegistry\Enums\ApproverType;
use ArcheeNic\PermissionRegistry\Models\ApprovalPolicy;
use ArcheeNic\PermissionRegistry\Models\ApprovalPolicyApprover;
use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ApprovalPolicyManager extends Component
{
    public int $permissionId;

    public bool $hasPolicy = false;
    public ?int $policyId = null;
    public string $approvalType = 'single';
    public int $requiredCount = 1;
    public bool $isActive = true;
    public array $approvers = [];

    public string $newApproverType = 'virtual_user';
    public ?int $newApproverId = null;

    public ?string $flashMessage = null;
    public ?string $flashError = null;

    public function mount(int $permissionId): void
    {
        $this->permissionId = $permissionId;
        $this->loadPolicy();
    }

    public function loadPolicy(): void
    {
        $policy = ApprovalPolicy::where('permission_id', $this->permissionId)
            ->with('approvers')
            ->first();

        if ($policy) {
            $this->hasPolicy = true;
            $this->policyId = $policy->id;
            $this->approvalType = $policy->approval_type->value;
            $this->requiredCount = $policy->required_count;
            $this->isActive = $policy->is_active;
            $this->approvers = $policy->approvers->map(fn ($a) => [
                'id' => $a->id,
                'type' => $a->approver_type->value,
                'approver_id' => $a->approver_id,
                'label' => $this->resolveApproverLabel($a->approver_type, $a->approver_id),
            ])->toArray();
        } else {
            $this->hasPolicy = false;
            $this->policyId = null;
            $this->approvers = [];
        }
    }

    public function enablePolicy(): void
    {
        $policy = ApprovalPolicy::create([
            ApprovalPolicy::PERMISSION_ID => $this->permissionId,
            ApprovalPolicy::APPROVAL_TYPE => ApprovalType::SINGLE->value,
            ApprovalPolicy::REQUIRED_COUNT => 1,
            ApprovalPolicy::IS_ACTIVE => true,
        ]);

        $this->loadPolicy();
        $this->flashMessage = __('permission-registry::Approval policy saved');
    }

    public function savePolicy(): void
    {
        if (! $this->policyId) {
            return;
        }

        $this->validateSavePolicy();

        ApprovalPolicy::where('id', $this->policyId)->update([
            ApprovalPolicy::APPROVAL_TYPE => $this->approvalType,
            ApprovalPolicy::REQUIRED_COUNT => $this->requiredCount,
            ApprovalPolicy::IS_ACTIVE => $this->isActive,
        ]);

        $this->flashMessage = __('permission-registry::Approval policy saved');
    }

    public function removePolicy(): void
    {
        if (! $this->policyId) {
            return;
        }

        $policy = ApprovalPolicy::find($this->policyId);
        if (! $policy) {
            return;
        }

        $hasRequests = DB::table('approval_requests')
            ->where('approval_policy_id', $this->policyId)
            ->exists();

        if ($hasRequests) {
            $policy->update([ApprovalPolicy::IS_ACTIVE => false]);
            $this->flashMessage = __('permission-registry::Approval disabled');
        } else {
            $policy->delete();
            $this->policyId = null;
            $this->hasPolicy = false;
            $this->approvers = [];
            $this->flashMessage = __('permission-registry::Approval policy removed');
        }

        $this->loadPolicy();
    }

    public function reEnablePolicy(): void
    {
        if (! $this->policyId) {
            return;
        }

        ApprovalPolicy::where('id', $this->policyId)->update([ApprovalPolicy::IS_ACTIVE => true]);
        $this->loadPolicy();
        $this->flashMessage = __('permission-registry::Approval enabled');
    }

    public function addApprover(): void
    {
        if (! $this->policyId || ! $this->newApproverId) {
            return;
        }

        $this->validateAddApprover();

        $exists = ApprovalPolicyApprover::where('approval_policy_id', $this->policyId)
            ->where('approver_type', $this->newApproverType)
            ->where('approver_id', $this->newApproverId)
            ->exists();

        if ($exists) {
            $this->flashError = __('permission-registry::Approver already added');
            return;
        }

        ApprovalPolicyApprover::create([
            ApprovalPolicyApprover::APPROVAL_POLICY_ID => $this->policyId,
            ApprovalPolicyApprover::APPROVER_TYPE => $this->newApproverType,
            ApprovalPolicyApprover::APPROVER_ID => $this->newApproverId,
        ]);

        $this->newApproverId = null;
        $this->loadPolicy();
    }

    public function removeApprover(int $approverId): void
    {
        ApprovalPolicyApprover::destroy($approverId);
        $this->loadPolicy();
    }

    public function getAvailableUsersProperty()
    {
        return VirtualUser::orderBy('name')->get(['id', 'name']);
    }

    public function getAvailablePositionsProperty()
    {
        return Position::orderBy('name')->get(['id', 'name']);
    }

    public function getApprovalTypesProperty(): array
    {
        return array_map(fn (ApprovalType $t) => [
            'value' => $t->value,
            'label' => $t->label(),
        ], ApprovalType::cases());
    }

    private function validateSavePolicy(): void
    {
        $validTypes = array_map(fn (ApprovalType $t) => $t->value, ApprovalType::cases());
        $validators = [
            'approvalType' => [Rule::in($validTypes)],
            'requiredCount' => ['integer', 'min:1', 'max:65535'],
            'isActive' => ['boolean'],
        ];
        $this->validate($validators);
    }

    private function validateAddApprover(): void
    {
        $validTypes = array_map(fn (ApproverType $t) => $t->value, ApproverType::cases());
        $this->validate([
            'newApproverType' => [Rule::in($validTypes)],
            'newApproverId' => ['required', 'integer', 'min:1'],
        ]);
    }

    private function resolveApproverLabel(ApproverType $type, int $id): string
    {
        return match ($type) {
            ApproverType::VIRTUAL_USER => VirtualUser::find($id)?->name ?? "#{$id}",
            ApproverType::POSITION => Position::find($id)?->name ?? "#{$id}",
        };
    }

    public function render()
    {
        return view('permission-registry::livewire.approval-policy-manager');
    }
}
