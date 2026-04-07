<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\CheckApprovalRequiredAction;
use ArcheeNic\PermissionRegistry\Actions\CreateApprovalRequestAction;
use ArcheeNic\PermissionRegistry\Actions\GetPendingApprovalsAction;
use ArcheeNic\PermissionRegistry\Actions\GrantPermissionAction;
use ArcheeNic\PermissionRegistry\Actions\ProcessApprovalDecisionAction;
use ArcheeNic\PermissionRegistry\Actions\ResolveApproversAction;
use ArcheeNic\PermissionRegistry\Enums\ApprovalDecisionType;
use ArcheeNic\PermissionRegistry\Enums\ApprovalRequestStatus;
use ArcheeNic\PermissionRegistry\Enums\ApprovalType;
use ArcheeNic\PermissionRegistry\Enums\ApproverType;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Enums\GrantedPermissionStatus;
use ArcheeNic\PermissionRegistry\Models\ApprovalDecision;
use ArcheeNic\PermissionRegistry\Models\ApprovalPolicy;
use ArcheeNic\PermissionRegistry\Models\ApprovalPolicyApprover;
use ArcheeNic\PermissionRegistry\Models\ApprovalRequest;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Models\VirtualUserPosition;
use ArcheeNic\PermissionRegistry\Services\PermissionDependencyResolver;
use ArcheeNic\PermissionRegistry\Services\PermissionTriggerExecutor;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use ArcheeNic\PermissionRegistry\ValueObjects\DependencyValidationResult;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;

class ApprovalFlowTest extends TestCase
{
    private VirtualUser $user;
    private VirtualUser $approver;
    private Permission $permission;

    /** Application user id used as approver in tests (resolver maps this to $this->approver) */
    private const APPROVER_USER_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Queue::fake();

        $this->user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $this->approver = VirtualUser::create(['name' => 'Approver', 'user_id' => self::APPROVER_USER_ID, 'status' => VirtualUserStatus::ACTIVE]);
        $this->permission = Permission::create(['service' => 'test', 'name' => 'test-perm']);

        $this->mockDependencyResolver();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mockDependencyResolver(): void
    {
        $mock = Mockery::mock(PermissionDependencyResolver::class);
        $mock->shouldReceive('validatePermissionDependencies')
            ->andReturn(DependencyValidationResult::valid());
        $this->app->instance(PermissionDependencyResolver::class, $mock);
    }

    private function createPolicyWithApprover(
        ApprovalType $type = ApprovalType::SINGLE,
        int $requiredCount = 1
    ): ApprovalPolicy {
        $policy = ApprovalPolicy::create([
            'permission_id' => $this->permission->id,
            'approval_type' => $type->value,
            'required_count' => $requiredCount,
            'is_active' => true,
        ]);

        ApprovalPolicyApprover::create([
            'approval_policy_id' => $policy->id,
            'approver_type' => ApproverType::VIRTUAL_USER->value,
            'approver_id' => $this->approver->id,
        ]);

        return $policy;
    }

    // --- CheckApprovalRequiredAction ---

    public function test_check_returns_null_when_no_policy(): void
    {
        $action = app(CheckApprovalRequiredAction::class);
        $result = $action->handle($this->permission->id);
        $this->assertNull($result);
    }

    public function test_check_returns_policy_when_active(): void
    {
        $this->createPolicyWithApprover();

        $action = app(CheckApprovalRequiredAction::class);
        $result = $action->handle($this->permission->id);

        $this->assertNotNull($result);
        $this->assertInstanceOf(ApprovalPolicy::class, $result);
    }

    public function test_check_returns_null_when_policy_inactive(): void
    {
        $policy = $this->createPolicyWithApprover();
        $policy->update(['is_active' => false]);

        $action = app(CheckApprovalRequiredAction::class);
        $result = $action->handle($this->permission->id);
        $this->assertNull($result);
    }

    // --- ResolveApproversAction ---

    public function test_resolve_direct_user_approvers(): void
    {
        $policy = $this->createPolicyWithApprover();

        $action = app(ResolveApproversAction::class);
        $result = $action->handle($policy);

        $this->assertCount(1, $result);
        $this->assertEquals($this->approver->id, $result->first());
    }

    public function test_resolve_position_based_approvers(): void
    {
        $position = Position::create(['name' => 'Manager']);
        VirtualUserPosition::create([
            'virtual_user_id' => $this->approver->id,
            'position_id' => $position->id,
        ]);

        $policy = ApprovalPolicy::create([
            'permission_id' => $this->permission->id,
            'approval_type' => ApprovalType::SINGLE->value,
            'required_count' => 1,
            'is_active' => true,
        ]);

        ApprovalPolicyApprover::create([
            'approval_policy_id' => $policy->id,
            'approver_type' => ApproverType::POSITION->value,
            'approver_id' => $position->id,
        ]);

        $action = app(ResolveApproversAction::class);
        $result = $action->handle($policy);

        $this->assertCount(1, $result);
        $this->assertEquals($this->approver->id, $result->first());
    }

    // --- GrantPermissionAction with approval ---

    public function test_grant_creates_awaiting_approval_when_policy_exists(): void
    {
        $this->createPolicyWithApprover();

        $action = app(GrantPermissionAction::class);
        $result = $action->handle($this->user->id, $this->permission->id, [], [], null, false, false, null, null, false);

        $this->assertEquals(GrantedPermissionStatus::AWAITING_APPROVAL->value, $result->status);
        $this->assertFalse($result->enabled);

        $this->assertDatabaseHas('approval_requests', [
            'granted_permission_id' => $result->id,
            'status' => ApprovalRequestStatus::PENDING->value,
        ]);
    }

    public function test_grant_skips_approval_when_no_policy(): void
    {
        $action = app(GrantPermissionAction::class);
        $result = $action->handle($this->user->id, $this->permission->id, [], [], null, true);

        $this->assertEquals(GrantedPermissionStatus::GRANTED->value, $result->status);
        $this->assertDatabaseMissing('approval_requests', [
            'granted_permission_id' => $result->id,
        ]);
    }

    public function test_grant_skips_approval_check_when_flag_set(): void
    {
        $this->createPolicyWithApprover();

        $action = app(GrantPermissionAction::class);
        $result = $action->handle(
            $this->user->id, $this->permission->id,
            [], [], null, true, false, null, null, true
        );

        $this->assertEquals(GrantedPermissionStatus::GRANTED->value, $result->status);
    }

    // --- ProcessApprovalDecisionAction ---

    public function test_single_approval_completes_request(): void
    {
        $this->createPolicyWithApprover();

        $grantAction = app(GrantPermissionAction::class);
        $grantedPermission = $grantAction->handle($this->user->id, $this->permission->id);

        $approvalRequest = ApprovalRequest::where('granted_permission_id', $grantedPermission->id)->first();

        $processAction = app(ProcessApprovalDecisionAction::class);
        $processAction->handle($approvalRequest, self::APPROVER_USER_ID, ApprovalDecisionType::APPROVED);

        $approvalRequest->refresh();
        $this->assertEquals(ApprovalRequestStatus::APPROVED, $approvalRequest->status);

        $grantedPermission->refresh();
        $this->assertNotEquals(GrantedPermissionStatus::AWAITING_APPROVAL->value, $grantedPermission->status);

        $this->assertDatabaseHas('approval_decisions', [
            'approval_request_id' => $approvalRequest->id,
            'approver_id' => self::APPROVER_USER_ID,
        ]);
        $this->assertEquals(self::APPROVER_USER_ID, $grantedPermission->confirmed_by);
    }

    public function test_rejection_rejects_granted_permission(): void
    {
        $this->createPolicyWithApprover();

        $grantAction = app(GrantPermissionAction::class);
        $grantedPermission = $grantAction->handle($this->user->id, $this->permission->id);

        $approvalRequest = ApprovalRequest::where('granted_permission_id', $grantedPermission->id)->first();

        $processAction = app(ProcessApprovalDecisionAction::class);
        $processAction->handle($approvalRequest, self::APPROVER_USER_ID, ApprovalDecisionType::REJECTED, 'Not allowed');

        $approvalRequest->refresh();
        $this->assertEquals(ApprovalRequestStatus::REJECTED, $approvalRequest->status);

        $grantedPermission->refresh();
        $this->assertEquals(GrantedPermissionStatus::REJECTED->value, $grantedPermission->status);
    }

    public function test_all_type_needs_all_approvers(): void
    {
        $approver2 = VirtualUser::create(['name' => 'Approver 2', 'user_id' => 2, 'status' => VirtualUserStatus::ACTIVE]);

        $policy = ApprovalPolicy::create([
            'permission_id' => $this->permission->id,
            'approval_type' => ApprovalType::ALL->value,
            'required_count' => 2,
            'is_active' => true,
        ]);

        ApprovalPolicyApprover::create([
            'approval_policy_id' => $policy->id,
            'approver_type' => ApproverType::VIRTUAL_USER->value,
            'approver_id' => $this->approver->id,
        ]);
        ApprovalPolicyApprover::create([
            'approval_policy_id' => $policy->id,
            'approver_type' => ApproverType::VIRTUAL_USER->value,
            'approver_id' => $approver2->id,
        ]);

        $grantAction = app(GrantPermissionAction::class);
        $grantedPermission = $grantAction->handle($this->user->id, $this->permission->id);

        $approvalRequest = ApprovalRequest::where('granted_permission_id', $grantedPermission->id)->first();

        $processAction = app(ProcessApprovalDecisionAction::class);

        $processAction->handle($approvalRequest, self::APPROVER_USER_ID, ApprovalDecisionType::APPROVED);
        $approvalRequest->refresh();
        $this->assertEquals(ApprovalRequestStatus::PENDING, $approvalRequest->status);

        $processAction->handle($approvalRequest, 2, ApprovalDecisionType::APPROVED);
        $approvalRequest->refresh();
        $this->assertEquals(ApprovalRequestStatus::APPROVED, $approvalRequest->status);
    }

    public function test_n_of_m_type_needs_required_count(): void
    {
        $approver2 = VirtualUser::create(['name' => 'Approver 2', 'user_id' => 2, 'status' => VirtualUserStatus::ACTIVE]);
        $approver3 = VirtualUser::create(['name' => 'Approver 3', 'user_id' => 3, 'status' => VirtualUserStatus::ACTIVE]);

        $policy = ApprovalPolicy::create([
            'permission_id' => $this->permission->id,
            'approval_type' => ApprovalType::N_OF_M->value,
            'required_count' => 2,
            'is_active' => true,
        ]);

        foreach ([$this->approver, $approver2, $approver3] as $a) {
            ApprovalPolicyApprover::create([
                'approval_policy_id' => $policy->id,
                'approver_type' => ApproverType::VIRTUAL_USER->value,
                'approver_id' => $a->id,
            ]);
        }

        $grantAction = app(GrantPermissionAction::class);
        $grantedPermission = $grantAction->handle($this->user->id, $this->permission->id);

        $approvalRequest = ApprovalRequest::where('granted_permission_id', $grantedPermission->id)->first();

        $processAction = app(ProcessApprovalDecisionAction::class);

        $processAction->handle($approvalRequest, self::APPROVER_USER_ID, ApprovalDecisionType::APPROVED);
        $approvalRequest->refresh();
        $this->assertEquals(ApprovalRequestStatus::PENDING, $approvalRequest->status);

        $processAction->handle($approvalRequest, 2, ApprovalDecisionType::APPROVED);
        $approvalRequest->refresh();
        $this->assertEquals(ApprovalRequestStatus::APPROVED, $approvalRequest->status);
    }

    // --- GetPendingApprovalsAction ---

    public function test_get_pending_approvals_for_user(): void
    {
        $this->createPolicyWithApprover();

        $grantAction = app(GrantPermissionAction::class);
        $grantAction->handle($this->user->id, $this->permission->id);

        $action = app(GetPendingApprovalsAction::class);
        $result = $action->handle(self::APPROVER_USER_ID);

        $this->assertCount(1, $result);
    }

    public function test_get_pending_approvals_excludes_already_decided(): void
    {
        $this->createPolicyWithApprover();

        $grantAction = app(GrantPermissionAction::class);
        $grantedPermission = $grantAction->handle($this->user->id, $this->permission->id);

        $approvalRequest = ApprovalRequest::where('granted_permission_id', $grantedPermission->id)->first();

        $processAction = app(ProcessApprovalDecisionAction::class);
        $processAction->handle($approvalRequest, self::APPROVER_USER_ID, ApprovalDecisionType::APPROVED);

        $action = app(GetPendingApprovalsAction::class);
        $result = $action->handle(self::APPROVER_USER_ID);

        $this->assertCount(0, $result);
    }

    public function test_get_pending_approvals_by_position(): void
    {
        $position = Position::create(['name' => 'Manager']);
        VirtualUserPosition::create([
            'virtual_user_id' => $this->approver->id,
            'position_id' => $position->id,
        ]);

        $policy = ApprovalPolicy::create([
            'permission_id' => $this->permission->id,
            'approval_type' => ApprovalType::SINGLE->value,
            'required_count' => 1,
            'is_active' => true,
        ]);
        ApprovalPolicyApprover::create([
            'approval_policy_id' => $policy->id,
            'approver_type' => ApproverType::POSITION->value,
            'approver_id' => $position->id,
        ]);

        $grantAction = app(GrantPermissionAction::class);
        $grantAction->handle($this->user->id, $this->permission->id);

        $action = app(GetPendingApprovalsAction::class);
        $result = $action->handle(self::APPROVER_USER_ID);

        $this->assertCount(1, $result);
    }

    // --- Model relationships ---

    public function test_approval_policy_belongs_to_permission(): void
    {
        $policy = $this->createPolicyWithApprover();
        $this->assertEquals($this->permission->id, $policy->permission->id);
    }

    public function test_approval_request_relationships(): void
    {
        $this->createPolicyWithApprover();

        $grantAction = app(GrantPermissionAction::class);
        $grantedPermission = $grantAction->handle($this->user->id, $this->permission->id);

        $request = ApprovalRequest::where('granted_permission_id', $grantedPermission->id)->first();

        $this->assertNotNull($request->grantedPermission);
        $this->assertNotNull($request->approvalPolicy);
    }

    public function test_permission_has_approval_policy_relationship(): void
    {
        $this->createPolicyWithApprover();
        $this->permission->refresh();

        $this->assertNotNull($this->permission->approvalPolicy);
        $this->assertInstanceOf(ApprovalPolicy::class, $this->permission->approvalPolicy);
    }

    public function test_granted_permission_has_approval_requests_relationship(): void
    {
        $this->createPolicyWithApprover();

        $grantAction = app(GrantPermissionAction::class);
        $grantedPermission = $grantAction->handle($this->user->id, $this->permission->id);

        $this->assertCount(1, $grantedPermission->approvalRequests);
    }
}
