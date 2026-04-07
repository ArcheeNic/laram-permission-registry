<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Services;

use ArcheeNic\PermissionRegistry\Contracts\PermissionTriggerInterface;
use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\HrEventTriggerAssignment;
use ArcheeNic\PermissionRegistry\Models\PermissionTrigger;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Services\HrEventTriggerExecutor;
use ArcheeNic\PermissionRegistry\Services\TriggerDiscoveryService;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use ArcheeNic\PermissionRegistry\ValueObjects\TriggerContext;
use ArcheeNic\PermissionRegistry\ValueObjects\TriggerResult;
use Illuminate\Support\Facades\DB;
use Mockery;

class HrEventTriggerExecutorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $mockDiscovery = Mockery::mock(TriggerDiscoveryService::class);
        $mockDiscovery->shouldReceive('discover')
            ->andReturn([
                ['class_name' => HrEventSuccessTestTrigger::class],
                ['class_name' => HrEventSecondSuccessTestTrigger::class],
                ['class_name' => HrEventFailTestTrigger::class],
                ['class_name' => HrEventAwaitingResolutionTestTrigger::class],
            ]);
        $this->app->instance(TriggerDiscoveryService::class, $mockDiscovery);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_executes_hire_triggers_in_order_and_stops_on_failure(): void
    {
        $user = VirtualUser::create([
            'name' => 'HR Test User',
            'employee_category' => EmployeeCategory::STAFF,
            'status' => VirtualUserStatus::ACTIVE,
        ]);

        $successTrigger = PermissionTrigger::create([
            'name' => 'HR Success Trigger',
            'class_name' => HrEventSuccessTestTrigger::class,
            'type' => 'both',
            'is_active' => true,
        ]);
        $failTrigger = PermissionTrigger::create([
            'name' => 'HR Fail Trigger',
            'class_name' => HrEventFailTestTrigger::class,
            'type' => 'both',
            'is_active' => true,
        ]);

        HrEventSuccessTestTrigger::$executed = false;
        HrEventFailTestTrigger::$executed = false;

        HrEventTriggerAssignment::create([
            'event_type' => 'hire',
            'employee_category' => EmployeeCategory::STAFF,
            'permission_trigger_id' => $successTrigger->id,
            'order' => 1,
            'is_enabled' => true,
        ]);
        HrEventTriggerAssignment::create([
            'event_type' => 'hire',
            'employee_category' => EmployeeCategory::STAFF,
            'permission_trigger_id' => $failTrigger->id,
            'order' => 2,
            'is_enabled' => true,
        ]);

        $result = app(HrEventTriggerExecutor::class)->execute($user->id, 'hire');

        $this->assertFalse($result);
        $this->assertTrue(HrEventSuccessTestTrigger::$executed);
        $this->assertTrue(HrEventFailTestTrigger::$executed);
    }

    public function test_executes_fire_triggers_only_for_fire_event(): void
    {
        $user = VirtualUser::create([
            'name' => 'Fire User',
            'employee_category' => EmployeeCategory::STAFF,
            'status' => VirtualUserStatus::ACTIVE,
        ]);

        $hireTrigger = PermissionTrigger::create([
            'name' => 'Hire Trigger Only',
            'class_name' => HrEventSuccessTestTrigger::class,
            'type' => 'both',
            'is_active' => true,
        ]);
        $fireTrigger = PermissionTrigger::create([
            'name' => 'Fire Trigger Only',
            'class_name' => HrEventSecondSuccessTestTrigger::class,
            'type' => 'both',
            'is_active' => true,
        ]);

        HrEventSuccessTestTrigger::$executed = false;
        HrEventSecondSuccessTestTrigger::$executed = false;

        HrEventTriggerAssignment::create([
            'event_type' => 'hire',
            'employee_category' => EmployeeCategory::STAFF,
            'permission_trigger_id' => $hireTrigger->id,
            'order' => 1,
            'is_enabled' => true,
        ]);
        HrEventTriggerAssignment::create([
            'event_type' => 'fire',
            'employee_category' => EmployeeCategory::STAFF,
            'permission_trigger_id' => $fireTrigger->id,
            'order' => 1,
            'is_enabled' => true,
        ]);

        $result = app(HrEventTriggerExecutor::class)->execute($user->id, 'fire');

        $this->assertTrue($result);
        $this->assertFalse(HrEventSuccessTestTrigger::$executed);
        $this->assertTrue(HrEventSecondSuccessTestTrigger::$executed);
    }

    public function test_executes_only_category_specific_triggers(): void
    {
        $user = VirtualUser::create([
            'name' => 'Contractor User',
            'employee_category' => EmployeeCategory::CONTRACTOR,
            'status' => VirtualUserStatus::ACTIVE,
        ]);

        $staffTrigger = PermissionTrigger::create([
            'name' => 'Staff Trigger',
            'class_name' => HrEventSuccessTestTrigger::class,
            'type' => 'both',
            'is_active' => true,
        ]);
        $contractorTrigger = PermissionTrigger::create([
            'name' => 'Contractor Trigger',
            'class_name' => HrEventSecondSuccessTestTrigger::class,
            'type' => 'both',
            'is_active' => true,
        ]);

        HrEventSuccessTestTrigger::$executed = false;
        HrEventSecondSuccessTestTrigger::$executed = false;

        HrEventTriggerAssignment::create([
            'event_type' => 'hire',
            'employee_category' => EmployeeCategory::STAFF,
            'permission_trigger_id' => $staffTrigger->id,
            'order' => 1,
            'is_enabled' => true,
        ]);
        HrEventTriggerAssignment::create([
            'event_type' => 'hire',
            'employee_category' => EmployeeCategory::CONTRACTOR,
            'permission_trigger_id' => $contractorTrigger->id,
            'order' => 1,
            'is_enabled' => true,
        ]);

        $result = app(HrEventTriggerExecutor::class)->execute($user->id, 'hire');

        $this->assertTrue($result);
        $this->assertFalse(HrEventSuccessTestTrigger::$executed);
        $this->assertTrue(HrEventSecondSuccessTestTrigger::$executed);
    }

    public function test_returns_false_when_user_category_invalid(): void
    {
        $user = VirtualUser::create([
            'name' => 'No Category User',
            'employee_category' => EmployeeCategory::STAFF,
            'status' => VirtualUserStatus::ACTIVE,
        ]);
        DB::table('virtual_users')
            ->where('id', $user->id)
            ->update(['employee_category' => 'invalid-category']);

        $result = app(HrEventTriggerExecutor::class)->execute($user->id, 'hire');

        $this->assertFalse($result);
    }

    public function test_creates_execution_logs_and_marks_awaiting_resolution(): void
    {
        $user = VirtualUser::create([
            'name' => 'Awaiting User',
            'employee_category' => EmployeeCategory::STAFF,
            'status' => VirtualUserStatus::ACTIVE,
        ]);

        $successTrigger = PermissionTrigger::create([
            'name' => 'Success Trigger',
            'class_name' => HrEventSuccessTestTrigger::class,
            'type' => 'both',
            'is_active' => true,
        ]);
        $awaitingTrigger = PermissionTrigger::create([
            'name' => 'Awaiting Trigger',
            'class_name' => HrEventAwaitingResolutionTestTrigger::class,
            'type' => 'both',
            'is_active' => true,
        ]);
        $neverTrigger = PermissionTrigger::create([
            'name' => 'Never Trigger',
            'class_name' => HrEventSecondSuccessTestTrigger::class,
            'type' => 'both',
            'is_active' => true,
        ]);

        HrEventSuccessTestTrigger::$executed = false;
        HrEventAwaitingResolutionTestTrigger::$executed = false;
        HrEventSecondSuccessTestTrigger::$executed = false;

        HrEventTriggerAssignment::create([
            'event_type' => 'hire',
            'employee_category' => EmployeeCategory::STAFF,
            'permission_trigger_id' => $successTrigger->id,
            'order' => 1,
            'is_enabled' => true,
        ]);
        HrEventTriggerAssignment::create([
            'event_type' => 'hire',
            'employee_category' => EmployeeCategory::STAFF,
            'permission_trigger_id' => $awaitingTrigger->id,
            'order' => 2,
            'is_enabled' => true,
        ]);
        HrEventTriggerAssignment::create([
            'event_type' => 'hire',
            'employee_category' => EmployeeCategory::STAFF,
            'permission_trigger_id' => $neverTrigger->id,
            'order' => 3,
            'is_enabled' => true,
        ]);

        $result = app(HrEventTriggerExecutor::class)->execute($user->id, 'hire');

        $this->assertFalse($result);
        $this->assertTrue(HrEventSuccessTestTrigger::$executed);
        $this->assertTrue(HrEventAwaitingResolutionTestTrigger::$executed);
        $this->assertFalse(HrEventSecondSuccessTestTrigger::$executed);

        $this->assertDatabaseHas('hr_trigger_execution_logs', [
            'virtual_user_id' => $user->id,
            'permission_trigger_id' => $successTrigger->id,
            'event_type' => 'hire',
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('hr_trigger_execution_logs', [
            'virtual_user_id' => $user->id,
            'permission_trigger_id' => $awaitingTrigger->id,
            'event_type' => 'hire',
            'status' => 'awaiting_resolution',
        ]);
    }

    public function test_execute_chain_from_index_retries_from_specific_step(): void
    {
        $user = VirtualUser::create([
            'name' => 'Retry User',
            'employee_category' => EmployeeCategory::STAFF,
            'status' => VirtualUserStatus::ACTIVE,
        ]);

        $firstTrigger = PermissionTrigger::create([
            'name' => 'First Trigger',
            'class_name' => HrEventSuccessTestTrigger::class,
            'type' => 'both',
            'is_active' => true,
        ]);
        $secondTrigger = PermissionTrigger::create([
            'name' => 'Second Trigger',
            'class_name' => HrEventSecondSuccessTestTrigger::class,
            'type' => 'both',
            'is_active' => true,
        ]);

        HrEventSuccessTestTrigger::$executed = false;
        HrEventSecondSuccessTestTrigger::$executed = false;
        HrEventSecondSuccessTestTrigger::$lastGlobalFields = [];

        HrEventTriggerAssignment::create([
            'event_type' => 'hire',
            'employee_category' => EmployeeCategory::STAFF,
            'permission_trigger_id' => $firstTrigger->id,
            'order' => 1,
            'is_enabled' => true,
        ]);
        HrEventTriggerAssignment::create([
            'event_type' => 'hire',
            'employee_category' => EmployeeCategory::STAFF,
            'permission_trigger_id' => $secondTrigger->id,
            'order' => 2,
            'is_enabled' => true,
        ]);

        $result = app(HrEventTriggerExecutor::class)->executeChainFromIndex(
            $user->id,
            'hire',
            1,
            ['override_email' => 'retry@example.com']
        );

        $this->assertTrue($result);
        $this->assertFalse(HrEventSuccessTestTrigger::$executed);
        $this->assertTrue(HrEventSecondSuccessTestTrigger::$executed);
        $this->assertSame('retry@example.com', HrEventSecondSuccessTestTrigger::$lastGlobalFields['override_email'] ?? null);

        $this->assertDatabaseMissing('hr_trigger_execution_logs', [
            'virtual_user_id' => $user->id,
            'permission_trigger_id' => $firstTrigger->id,
        ]);
        $this->assertDatabaseHas('hr_trigger_execution_logs', [
            'virtual_user_id' => $user->id,
            'permission_trigger_id' => $secondTrigger->id,
            'status' => 'success',
        ]);
    }
}

class HrEventSuccessTestTrigger implements PermissionTriggerInterface
{
    public static bool $executed = false;

    public function execute(TriggerContext $context): TriggerResult
    {
        self::$executed = true;
        return TriggerResult::success();
    }

    public function canRollback(): bool
    {
        return false;
    }

    public function rollback(TriggerContext $context): void
    {
    }
}

class HrEventSecondSuccessTestTrigger implements PermissionTriggerInterface
{
    public static bool $executed = false;
    public static array $lastGlobalFields = [];

    public function execute(TriggerContext $context): TriggerResult
    {
        self::$executed = true;
        self::$lastGlobalFields = $context->globalFields;
        return TriggerResult::success();
    }

    public function canRollback(): bool
    {
        return false;
    }

    public function rollback(TriggerContext $context): void
    {
    }
}

class HrEventFailTestTrigger implements PermissionTriggerInterface
{
    public static bool $executed = false;

    public function execute(TriggerContext $context): TriggerResult
    {
        self::$executed = true;
        return TriggerResult::failure('Forced failure');
    }

    public function canRollback(): bool
    {
        return false;
    }

    public function rollback(TriggerContext $context): void
    {
    }
}

class HrEventAwaitingResolutionTestTrigger implements PermissionTriggerInterface
{
    public static bool $executed = false;

    public function execute(TriggerContext $context): TriggerResult
    {
        self::$executed = true;
        return TriggerResult::awaitingResolution('Needs manual resolution', ['reason' => 'duplicate_email']);
    }

    public function canRollback(): bool
    {
        return false;
    }

    public function rollback(TriggerContext $context): void
    {
    }
}
