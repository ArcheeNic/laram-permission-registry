<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use App\Models\User;
use ArcheeNic\PermissionRegistry\Actions\ResolveEmailConflictAction;
use ArcheeNic\PermissionRegistry\Actions\RetryHrTriggerAction;
use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use ArcheeNic\PermissionRegistry\Enums\HrTriggerExecutionStatus;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\HrEventTriggerAssignment;
use ArcheeNic\PermissionRegistry\Models\HrTriggerExecutionLog;
use ArcheeNic\PermissionRegistry\Models\PermissionTrigger;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Mockery;

class ResolveEmailConflictActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_increment_strategy_uses_suggested_email_for_retry(): void
    {
        $log = $this->createAwaitingLog(['suggested_email' => 'test.user1@example.com']);
        $actor = User::factory()->create();

        $retry = Mockery::mock(RetryHrTriggerAction::class);
        $retry->shouldReceive('execute')
            ->once()
            ->with($log->id, ['override_email' => 'test.user1@example.com'], $actor->id)
            ->andReturn(true);
        $this->app->instance(RetryHrTriggerAction::class, $retry);

        $result = app(ResolveEmailConflictAction::class)->execute($log->id, 'increment', [], $actor->id);

        $this->assertTrue($result);
        $this->assertDatabaseHas('hr_trigger_execution_logs', [
            'id' => $log->id,
            'actor_id' => $actor->id,
        ]);
    }

    public function test_custom_email_strategy_rejects_invalid_email(): void
    {
        $log = $this->createAwaitingLog(['suggested_email' => 'test.user1@example.com']);
        $actor = User::factory()->create();

        $retry = Mockery::mock(RetryHrTriggerAction::class);
        $retry->shouldNotReceive('execute');
        $this->app->instance(RetryHrTriggerAction::class, $retry);

        $result = app(ResolveEmailConflictAction::class)->execute($log->id, 'custom_email', ['email' => 'bad-value'], $actor->id);

        $this->assertFalse($result);
    }

    public function test_cancel_strategy_marks_log_as_failed(): void
    {
        $log = $this->createAwaitingLog(['suggested_email' => 'test.user1@example.com']);
        $actor = User::factory()->create();

        $retry = Mockery::mock(RetryHrTriggerAction::class);
        $retry->shouldNotReceive('execute');
        $this->app->instance(RetryHrTriggerAction::class, $retry);

        $result = app(ResolveEmailConflictAction::class)->execute($log->id, 'cancel', [], $actor->id);

        $this->assertTrue($result);
        $this->assertDatabaseHas('hr_trigger_execution_logs', [
            'id' => $log->id,
            'status' => HrTriggerExecutionStatus::FAILED->value,
            'actor_id' => $actor->id,
        ]);
    }

    public function test_returns_false_for_non_awaiting_log_status(): void
    {
        $log = $this->createAwaitingLog(['suggested_email' => 'test.user1@example.com']);
        $log->update(['status' => HrTriggerExecutionStatus::SUCCESS->value]);

        $retry = Mockery::mock(RetryHrTriggerAction::class);
        $retry->shouldNotReceive('execute');
        $this->app->instance(RetryHrTriggerAction::class, $retry);

        $result = app(ResolveEmailConflictAction::class)->execute($log->id, 'increment');

        $this->assertFalse($result);
    }

    private function createAwaitingLog(array $meta): HrTriggerExecutionLog
    {
        $user = VirtualUser::create([
            'name' => 'Resolve User',
            'status' => VirtualUserStatus::ACTIVE,
            'employee_category' => EmployeeCategory::STAFF,
        ]);

        $trigger = PermissionTrigger::create([
            'name' => 'Awaiting trigger',
            'class_name' => \stdClass::class,
            'type' => 'both',
            'is_active' => true,
        ]);
        $assignment = HrEventTriggerAssignment::create([
            'event_type' => 'hire',
            'employee_category' => EmployeeCategory::STAFF,
            'permission_trigger_id' => $trigger->id,
            'order' => 1,
            'is_enabled' => true,
        ]);

        return HrTriggerExecutionLog::create([
            'virtual_user_id' => $user->id,
            'hr_event_trigger_assignment_id' => $assignment->id,
            'permission_trigger_id' => $trigger->id,
            'event_type' => 'hire',
            'employee_category' => EmployeeCategory::STAFF->value,
            'status' => HrTriggerExecutionStatus::AWAITING_RESOLUTION->value,
            'meta' => $meta,
        ]);
    }
}
