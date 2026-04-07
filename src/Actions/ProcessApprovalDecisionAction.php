<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Contracts\UserToVirtualUserResolver;
use ArcheeNic\PermissionRegistry\Enums\ApprovalDecisionType;
use ArcheeNic\PermissionRegistry\Enums\ApprovalRequestStatus;
use ArcheeNic\PermissionRegistry\Enums\ApprovalType;
use ArcheeNic\PermissionRegistry\Enums\GrantedPermissionStatus;
use ArcheeNic\PermissionRegistry\Events\ApprovalCompleted;
use ArcheeNic\PermissionRegistry\Events\ApprovalDecisionMade;
use ArcheeNic\PermissionRegistry\Models\ApprovalDecision;
use ArcheeNic\PermissionRegistry\Models\ApprovalRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

class ProcessApprovalDecisionAction
{
    private const MAX_COMMENT_LENGTH = 2000;

    public function __construct(
        private ResolveApproversAction $resolveApprovers,
        private GrantPermissionAction $grantPermission,
        private UserToVirtualUserResolver $userResolver,
        private AuditLogger $auditLogger
    ) {
    }

    /**
     * @param int $approverId Application user id (users.id) of the person making the decision
     */
    public function handle(
        ApprovalRequest $approvalRequest,
        int $approverId,
        ApprovalDecisionType $decision,
        ?string $comment = null
    ): ApprovalDecision {
        $virtualUserId = $this->userResolver->resolve($approverId);
        if ($virtualUserId === null) {
            throw ValidationException::withMessages([
                'approver' => [__('permission-registry::approvals.not_approver')],
            ]);
        }

        if ($approvalRequest->requested_by === $virtualUserId) {
            throw ValidationException::withMessages([
                'approver' => [__('permission-registry::messages.cannot_self_approve')],
            ]);
        }

        $allowedVirtualUserIds = $this->resolveApprovers->handle($approvalRequest->approvalPolicy);
        if (! $allowedVirtualUserIds->contains($virtualUserId)) {
            throw ValidationException::withMessages([
                'approver' => [__('permission-registry::approvals.not_approver')],
            ]);
        }

        if ($approvalRequest->status !== ApprovalRequestStatus::PENDING) {
            throw ValidationException::withMessages([
                'request' => [__('permission-registry::approvals.request_already_resolved')],
            ]);
        }

        if ($comment !== null && strlen($comment) > self::MAX_COMMENT_LENGTH) {
            throw ValidationException::withMessages([
                'comment' => [__('permission-registry::approvals.comment_too_long', ['max' => self::MAX_COMMENT_LENGTH])],
            ]);
        }

        return DB::transaction(function () use ($approvalRequest, $approverId, $decision, $comment) {
            $locked = ApprovalRequest::where('id', $approvalRequest->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== ApprovalRequestStatus::PENDING) {
                throw ValidationException::withMessages([
                    'request' => [__('permission-registry::approvals.request_already_resolved')],
                ]);
            }

            $approvalDecision = ApprovalDecision::create([
                ApprovalDecision::APPROVAL_REQUEST_ID => $approvalRequest->id,
                ApprovalDecision::APPROVER_ID => $approverId,
                ApprovalDecision::DECISION => $decision->value,
                ApprovalDecision::COMMENT => $comment,
                ApprovalDecision::DECIDED_AT => now(),
            ]);

            Event::dispatch(new ApprovalDecisionMade($approvalRequest, $approvalDecision));

            $this->auditLogger->log('approval.' . $decision->value, $approverId, [
                'approval_request_id' => $approvalRequest->id,
                'granted_permission_id' => $approvalRequest->granted_permission_id,
                'comment' => $comment,
            ]);

            $this->evaluateQuorum($approvalRequest->fresh(['decisions', 'approvalPolicy.approvers']));

            return $approvalDecision;
        });
    }

    private function evaluateQuorum(ApprovalRequest $request): void
    {
        $policy = $request->approvalPolicy;
        $decisions = $request->decisions;

        $rejections = $decisions->where('decision', ApprovalDecisionType::REJECTED->value)->count();
        if ($rejections > 0) {
            $this->reject($request);
            return;
        }

        $approvals = $decisions->where('decision', ApprovalDecisionType::APPROVED->value)->count();

        $reached = match ($policy->approval_type) {
            ApprovalType::SINGLE => $approvals >= 1,
            ApprovalType::ALL => $approvals >= $this->resolveApprovers->handle($policy)->count(),
            ApprovalType::N_OF_M => $approvals >= $policy->required_count,
        };

        if ($reached) {
            $this->approve($request);
        }
    }

    private function approve(ApprovalRequest $request): void
    {
        $request->update([
            ApprovalRequest::STATUS => ApprovalRequestStatus::APPROVED->value,
            ApprovalRequest::RESOLVED_AT => now(),
        ]);

        Event::dispatch(new ApprovalCompleted($request, ApprovalRequestStatus::APPROVED));

        $grantedPermission = $request->grantedPermission;

        $this->grantPermission->handle(
            userId: $grantedPermission->virtual_user_id,
            permissionId: $grantedPermission->permission_id,
            requestedBy: $grantedPermission->requested_by,
            confirmedBy: $request->decisions->last()?->approver_id,
            skipApprovalCheck: true
        );
    }

    private function reject(ApprovalRequest $request): void
    {
        $request->update([
            ApprovalRequest::STATUS => ApprovalRequestStatus::REJECTED->value,
            ApprovalRequest::RESOLVED_AT => now(),
        ]);

        $request->grantedPermission->update([
            'status' => GrantedPermissionStatus::REJECTED->value,
            'status_message' => __('permission-registry::approvals.rejected_by_approver'),
        ]);

        Event::dispatch(new ApprovalCompleted($request, ApprovalRequestStatus::REJECTED));
    }
}
