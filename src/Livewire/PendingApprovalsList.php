<?php

namespace ArcheeNic\PermissionRegistry\Livewire;

use ArcheeNic\PermissionRegistry\Actions\GetPendingApprovalsAction;
use ArcheeNic\PermissionRegistry\Actions\ProcessApprovalDecisionAction;
use ArcheeNic\PermissionRegistry\Enums\ApprovalDecisionType;
use ArcheeNic\PermissionRegistry\Models\ApprovalRequest;
use Livewire\Component;

class PendingApprovalsList extends Component
{
    public ?int $currentUserId = null;
    public ?int $selectedRequestId = null;
    public string $comment = '';

    public ?string $flashMessage = null;
    public ?string $flashError = null;

    protected $listeners = ['refreshApprovals' => '$refresh'];

    public function mount(?int $currentUserId = null): void
    {
        $this->currentUserId = $currentUserId;
    }

    public function selectRequest(int $requestId): void
    {
        $this->selectedRequestId = $requestId;
        $this->comment = '';
    }

    public function closeDetail(): void
    {
        $this->selectedRequestId = null;
        $this->comment = '';
    }

    public function approve(): void
    {
        $this->makeDecision(ApprovalDecisionType::APPROVED);
    }

    public function reject(): void
    {
        $this->makeDecision(ApprovalDecisionType::REJECTED);
    }

    private function makeDecision(ApprovalDecisionType $decision): void
    {
        if (! $this->currentUserId || ! $this->selectedRequestId) {
            return;
        }

        $request = ApprovalRequest::find($this->selectedRequestId);
        if (! $request) {
            return;
        }

        try {
            $action = app(ProcessApprovalDecisionAction::class);
            $action->handle($request, $this->currentUserId, $decision, $this->comment ?: null);

            $this->flashMessage = __('permission-registry::Decision saved');
            $this->selectedRequestId = null;
            $this->comment = '';
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->flashError = $e->validator->errors()->first();
        } catch (\Exception $e) {
            $this->flashError = __('permission-registry::approvals.error_generic');
        }
    }

    public function getPendingApprovalsProperty()
    {
        if (! $this->currentUserId) {
            return collect();
        }

        return app(GetPendingApprovalsAction::class)->handle($this->currentUserId);
    }

    public function getSelectedRequestProperty()
    {
        if (! $this->selectedRequestId) {
            return null;
        }

        return ApprovalRequest::with([
            'grantedPermission.permission',
            'grantedPermission.user',
            'approvalPolicy.approvers',
            'decisions.approver',
        ])->find($this->selectedRequestId);
    }

    public function render()
    {
        return view('permission-registry::livewire.pending-approvals-list');
    }
}
