<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\AttachAccessEvidenceAction;
use ArcheeNic\PermissionRegistry\Actions\ConfirmManualProvisionAction;
use ArcheeNic\PermissionRegistry\Actions\GrantPermissionAction;
use ArcheeNic\PermissionRegistry\Actions\ProcessAccessAttestationDecisionAction;
use ArcheeNic\PermissionRegistry\Actions\ScheduleAccessAttestationAction;
use ArcheeNic\PermissionRegistry\Enums\AttestationStatus;
use ArcheeNic\PermissionRegistry\Enums\EvidenceType;
use ArcheeNic\PermissionRegistry\Enums\GrantedPermissionStatus;
use ArcheeNic\PermissionRegistry\Enums\ManagementMode;
use ArcheeNic\PermissionRegistry\Enums\ManualTaskStatus;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Events\AfterPermissionGranted;
use ArcheeNic\PermissionRegistry\Models\AccessAttestation;
use ArcheeNic\PermissionRegistry\Models\AccessEvidence;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\ManualProvisionTask;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Services\PermissionDependencyResolver;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use ArcheeNic\PermissionRegistry\ValueObjects\DependencyValidationResult;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Mockery;

class GovernanceFlowTest extends TestCase
{
    private VirtualUser $user;
    private Permission $automatedPermission;
    private Permission $manualPermission;
    private Permission $declarativePermission;
    private Permission $declarativePermissionWithAttestation;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Queue::fake();

        $this->user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);

        $this->automatedPermission = Permission::create([
            'service' => 'test',
            'name' => 'automated-perm',
            'management_mode' => ManagementMode::AUTOMATED->value,
        ]);

        $this->manualPermission = Permission::create([
            'service' => 'test',
            'name' => 'manual-perm',
            'management_mode' => ManagementMode::MANUAL->value,
            'risk_level' => 'low',
        ]);

        $this->declarativePermission = Permission::create([
            'service' => 'test',
            'name' => 'declarative-perm',
            'management_mode' => ManagementMode::DECLARATIVE->value,
        ]);

        $this->declarativePermissionWithAttestation = Permission::create([
            'service' => 'test',
            'name' => 'declarative-attested-perm',
            'management_mode' => ManagementMode::DECLARATIVE->value,
            'attestation_period_days' => 30,
        ]);

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

    // --- GrantPermissionAction: management_mode branching ---

    public function test_it_grants_permission_with_automated_mode_default_behavior(): void
    {
        $action = app(GrantPermissionAction::class);
        $result = $action->handle($this->user->id, $this->automatedPermission->id, [], [], null, true);

        $this->assertContains($result->status, [
            GrantedPermissionStatus::PENDING->value,
            GrantedPermissionStatus::GRANTED->value,
        ]);
        $this->assertTrue($result->enabled);
    }

    public function test_it_creates_manual_task_for_manual_mode(): void
    {
        $action = app(GrantPermissionAction::class);
        $result = $action->handle($this->user->id, $this->manualPermission->id);

        $this->assertEquals(GrantedPermissionStatus::MANUAL_PENDING->value, $result->status);
        $this->assertFalse($result->enabled);

        $this->assertDatabaseHas('manual_provision_tasks', [
            ManualProvisionTask::GRANTED_PERMISSION_ID => $result->id,
            ManualProvisionTask::STATUS => ManualTaskStatus::PENDING->value,
        ]);
    }

    public function test_it_creates_declared_access_for_declarative_mode(): void
    {
        $action = app(GrantPermissionAction::class);
        $result = $action->handle($this->user->id, $this->declarativePermission->id);

        $this->assertEquals(GrantedPermissionStatus::DECLARED->value, $result->status);
        $this->assertTrue($result->enabled);
    }

    public function test_it_schedules_attestation_for_declarative_with_period(): void
    {
        $action = app(GrantPermissionAction::class);
        $result = $action->handle($this->user->id, $this->declarativePermissionWithAttestation->id);

        $this->assertEquals(GrantedPermissionStatus::DECLARED->value, $result->status);

        $this->assertDatabaseHas('access_attestations', [
            AccessAttestation::GRANTED_PERMISSION_ID => $result->id,
            AccessAttestation::ATTESTATION_PERIOD_DAYS => 30,
            AccessAttestation::STATUS => AttestationStatus::PENDING->value,
        ]);
    }

    // --- ConfirmManualProvisionAction ---

    public function test_it_completes_manual_task_and_grants_permission(): void
    {
        $grantAction = app(GrantPermissionAction::class);
        $grantedPermission = $grantAction->handle($this->user->id, $this->manualPermission->id);

        $task = ManualProvisionTask::where(
            ManualProvisionTask::GRANTED_PERMISSION_ID, $grantedPermission->id
        )->first();

        $completedBy = 99;

        $confirmAction = app(ConfirmManualProvisionAction::class);
        $confirmAction->handle($task, $completedBy);

        $task->refresh();
        $this->assertEquals(ManualTaskStatus::COMPLETED, $task->status);

        $grantedPermission->refresh();
        $this->assertEquals(GrantedPermissionStatus::GRANTED->value, $grantedPermission->status);
        $this->assertTrue($grantedPermission->enabled);
        $this->assertEquals($completedBy, $grantedPermission->confirmed_by);

        Event::assertDispatched(AfterPermissionGranted::class, function ($event) {
            return $event->userId === $this->user->id
                && $event->permissionId === $this->manualPermission->id;
        });
    }

    public function test_it_attaches_evidence_on_task_completion(): void
    {
        $grantAction = app(GrantPermissionAction::class);
        $grantedPermission = $grantAction->handle($this->user->id, $this->manualPermission->id);

        $task = ManualProvisionTask::where(
            ManualProvisionTask::GRANTED_PERMISSION_ID, $grantedPermission->id
        )->first();

        $completedBy = 99;
        $evidenceData = [
            'type' => EvidenceType::TICKET->value,
            'value' => 'JIRA-1234',
            'meta' => ['source' => 'jira'],
        ];

        $confirmAction = app(ConfirmManualProvisionAction::class);
        $confirmAction->handle($task, $completedBy, $evidenceData);

        $this->assertDatabaseHas('access_evidences', [
            AccessEvidence::GRANTED_PERMISSION_ID => $grantedPermission->id,
            AccessEvidence::MANUAL_PROVISION_TASK_ID => $task->id,
            AccessEvidence::TYPE => EvidenceType::TICKET->value,
            AccessEvidence::VALUE => 'JIRA-1234',
            AccessEvidence::PROVIDED_BY => $completedBy,
        ]);
    }

    public function test_it_rejects_completing_already_completed_task(): void
    {
        $grantAction = app(GrantPermissionAction::class);
        $grantedPermission = $grantAction->handle($this->user->id, $this->manualPermission->id);

        $task = ManualProvisionTask::where(
            ManualProvisionTask::GRANTED_PERMISSION_ID, $grantedPermission->id
        )->first();

        $confirmAction = app(ConfirmManualProvisionAction::class);
        $confirmAction->handle($task, 99);

        $task->refresh();

        $this->expectException(ValidationException::class);
        $confirmAction->handle($task, 99);
    }

    // --- AttachAccessEvidenceAction ---

    public function test_it_creates_evidence_record(): void
    {
        $grantAction = app(GrantPermissionAction::class);
        $grantedPermission = $grantAction->handle($this->user->id, $this->manualPermission->id);

        $evidenceAction = app(AttachAccessEvidenceAction::class);
        $evidence = $evidenceAction->handle(
            grantedPermissionId: $grantedPermission->id,
            type: EvidenceType::URL->value,
            value: 'https://example.com/proof',
            providedBy: 42,
            meta: ['note' => 'screenshot link']
        );

        $this->assertDatabaseHas('access_evidences', [
            AccessEvidence::ID => $evidence->id,
            AccessEvidence::GRANTED_PERMISSION_ID => $grantedPermission->id,
            AccessEvidence::TYPE => EvidenceType::URL->value,
            AccessEvidence::VALUE => 'https://example.com/proof',
            AccessEvidence::PROVIDED_BY => 42,
        ]);
    }

    // --- ScheduleAccessAttestationAction ---

    public function test_it_schedules_attestation_with_correct_due_date(): void
    {
        $grantAction = app(GrantPermissionAction::class);
        $grantedPermission = $grantAction->handle(
            $this->user->id, $this->declarativePermission->id
        );

        $this->travelTo(now());

        $scheduleAction = app(ScheduleAccessAttestationAction::class);
        $attestation = $scheduleAction->handle($grantedPermission, 30);

        $this->assertNotNull($attestation);
        $this->assertEquals(30, $attestation->attestation_period_days);
        $this->assertEquals(
            now()->addDays(30)->startOfMinute()->toDateTimeString(),
            $attestation->due_at->startOfMinute()->toDateTimeString()
        );
        $this->assertEquals(AttestationStatus::PENDING, $attestation->status);
    }

    public function test_it_returns_null_when_no_period_set(): void
    {
        $grantAction = app(GrantPermissionAction::class);
        $grantedPermission = $grantAction->handle(
            $this->user->id, $this->declarativePermission->id
        );

        $scheduleAction = app(ScheduleAccessAttestationAction::class);
        $result = $scheduleAction->handle($grantedPermission);

        $this->assertNull($result);
    }

    // --- ProcessAccessAttestationDecisionAction ---

    public function test_it_confirms_attestation_and_schedules_next(): void
    {
        $grantAction = app(GrantPermissionAction::class);
        $grantedPermission = $grantAction->handle(
            $this->user->id, $this->declarativePermissionWithAttestation->id
        );

        $attestation = AccessAttestation::where(
            AccessAttestation::GRANTED_PERMISSION_ID, $grantedPermission->id
        )->first();

        $processAction = app(ProcessAccessAttestationDecisionAction::class);
        $processAction->handle($attestation, AttestationStatus::CONFIRMED, 1, 'Looks good');

        $attestation->refresh();
        $this->assertEquals(AttestationStatus::CONFIRMED, $attestation->status);
        $this->assertNotNull($attestation->decided_at);
        $this->assertEquals(1, $attestation->decided_by);

        $nextAttestation = AccessAttestation::where(AccessAttestation::GRANTED_PERMISSION_ID, $grantedPermission->id)
            ->where(AccessAttestation::STATUS, AttestationStatus::PENDING->value)
            ->where(AccessAttestation::ID, '!=', $attestation->id)
            ->first();

        $this->assertNotNull($nextAttestation);
        $this->assertEquals(30, $nextAttestation->attestation_period_days);
    }

    public function test_it_rejects_attestation_and_revokes_permission(): void
    {
        $grantAction = app(GrantPermissionAction::class);
        $grantedPermission = $grantAction->handle(
            $this->user->id, $this->declarativePermissionWithAttestation->id
        );

        $attestation = AccessAttestation::where(
            AccessAttestation::GRANTED_PERMISSION_ID, $grantedPermission->id
        )->first();

        $processAction = app(ProcessAccessAttestationDecisionAction::class);
        $result = $processAction->handle($attestation, AttestationStatus::REJECTED, 1, 'No longer needed');

        $this->assertEquals(AttestationStatus::REJECTED, $result->status);

        $this->assertDatabaseMissing('granted_permissions', [
            'id' => $grantedPermission->id,
        ]);
    }

    public function test_it_rejects_already_decided_attestation(): void
    {
        $grantAction = app(GrantPermissionAction::class);
        $grantedPermission = $grantAction->handle(
            $this->user->id, $this->declarativePermissionWithAttestation->id
        );

        $attestation = AccessAttestation::where(
            AccessAttestation::GRANTED_PERMISSION_ID, $grantedPermission->id
        )->first();

        $processAction = app(ProcessAccessAttestationDecisionAction::class);
        $processAction->handle($attestation, AttestationStatus::CONFIRMED, 1);

        $attestation->refresh();

        $this->expectException(ValidationException::class);
        $processAction->handle($attestation, AttestationStatus::CONFIRMED, 1);
    }

    // --- Enum tests ---

    public function test_it_returns_correct_management_mode_flags(): void
    {
        $this->assertTrue(ManagementMode::AUTOMATED->requiresTriggers());
        $this->assertFalse(ManagementMode::AUTOMATED->requiresManualTask());
        $this->assertFalse(ManagementMode::AUTOMATED->requiresAttestation());

        $this->assertFalse(ManagementMode::MANUAL->requiresTriggers());
        $this->assertTrue(ManagementMode::MANUAL->requiresManualTask());
        $this->assertFalse(ManagementMode::MANUAL->requiresAttestation());

        $this->assertFalse(ManagementMode::DECLARATIVE->requiresTriggers());
        $this->assertFalse(ManagementMode::DECLARATIVE->requiresManualTask());
        $this->assertTrue(ManagementMode::DECLARATIVE->requiresAttestation());
    }

    public function test_it_checks_terminal_states_for_manual_task_status(): void
    {
        $this->assertFalse(ManualTaskStatus::PENDING->isTerminal());
        $this->assertFalse(ManualTaskStatus::IN_PROGRESS->isTerminal());
        $this->assertTrue(ManualTaskStatus::COMPLETED->isTerminal());
        $this->assertTrue(ManualTaskStatus::CANCELLED->isTerminal());
        $this->assertTrue(ManualTaskStatus::EXPIRED->isTerminal());
    }
}
