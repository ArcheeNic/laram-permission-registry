<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\RetryHrTriggerAction;
use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use ArcheeNic\PermissionRegistry\Enums\HrTriggerExecutionStatus;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\HrEventTriggerAssignment;
use ArcheeNic\PermissionRegistry\Models\HrTriggerExecutionLog;
use ArcheeNic\PermissionRegistry\Models\PermissionTrigger;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Services\HrEventTriggerExecutor;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Mockery;

class RetryHrTriggerActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_retries_from_failed_assignment_index(): void
    {
        $user = VirtualUser::create([
            'name' => 'Retry HR',
            'status' => VirtualUserStatus::ACTIVE,
            'employee_category' => EmployeeCategory::STAFF,
        ]);

        $firstTrigger = PermissionTrigger::create([
            'name' => 'First trigger',
            'class_name' => \stdClass::class,
            'type' => 'both',
            'is_active' => true,
        ]);
        $failedTrigger = PermissionTrigger::create([
            'name' => 'Failed trigger',
            'class_name' => \ArrayObject::class,
            'type' => 'both',
            'is_active' => true,
        ]);

        HrEventTriggerAssignment::create([
            'event_type' => 'hire',
            'employee_category' => EmployeeCategory::STAFF,
            'permission_trigger_id' => $firstTrigger->id,
            'order' => 1,
            'is_enabled' => true,
        ]);
        $failedAssignment = HrEventTriggerAssignment::create([
            'event_type' => 'hire',
            'employee_category' => EmployeeCategory::STAFF,
            'permission_trigger_id' => $failedTrigger->id,
            'order' => 2,
            'is_enabled' => true,
        ]);

        $log = HrTriggerExecutionLog::create([
            'virtual_user_id' => $user->id,
            'hr_event_trigger_assignment_id' => $failedAssignment->id,
            'permission_trigger_id' => $failedTrigger->id,
            'event_type' => 'hire',
            'employee_category' => EmployeeCategory::STAFF->value,
            'status' => HrTriggerExecutionStatus::AWAITING_RESOLUTION->value,
        ]);

        $executor = Mockery::mock(HrEventTriggerExecutor::class);
        $executor->shouldReceive('executeChainFromIndex')
            ->once()
            ->with($user->id, 'hire', 1, ['override_email' => 'retry@example.com'])
            ->andReturn(true);
        $this->app->instance(HrEventTriggerExecutor::class, $executor);

        $result = app(RetryHrTriggerAction::class)->execute($log->id, ['override_email' => 'retry@example.com']);

        $this->assertTrue($result);
        $this->assertDatabaseHas('hr_trigger_execution_logs', [
            'id' => $log->id,
            'status' => HrTriggerExecutionStatus::SUCCESS->value,
        ]);
    }

    public function test_returns_false_when_log_not_found(): void
    {
        $executor = Mockery::mock(HrEventTriggerExecutor::class);
        $executor->shouldNotReceive('executeChainFromIndex');
        $this->app->instance(HrEventTriggerExecutor::class, $executor);

        $result = app(RetryHrTriggerAction::class)->execute(9999, []);

        $this->assertFalse($result);
    }
}
