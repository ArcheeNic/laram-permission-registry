<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Services;

use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionTrigger;
use ArcheeNic\PermissionRegistry\Models\PermissionTriggerAssignment;
use ArcheeNic\PermissionRegistry\Services\TriggerPermissionMatcherService;
use ArcheeNic\PermissionRegistry\Tests\TestCase;

class TriggerPermissionMatcherServiceTest extends TestCase
{
    private TriggerPermissionMatcherService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TriggerPermissionMatcherService::class);
    }

    public function test_match_by_departments_returns_multiple_permissions(): void
    {
        [$permissionA, $permissionB] = $this->createBitrixGrantAssignments('15', '412');

        $matched = $this->service->matchByDepartments(['15', '412'], ['App\\Triggers\\Bitrix24%']);

        $actual = $matched->pluck('permission_id')->all();
        sort($actual);
        $expected = [$permissionA->id, $permissionB->id];
        sort($expected);
        $this->assertSame($expected, $actual);
    }

    public function test_match_by_departments_ignores_disabled_assignments(): void
    {
        $permission = Permission::create([
            'service' => 'b24',
            'name' => 'Disabled',
            'description' => 'Disabled mapping',
        ]);

        $trigger = PermissionTrigger::create([
            'name' => 'Bitrix24 Add',
            'class_name' => 'App\\Triggers\\Bitrix24AddToDepartmentTrigger',
            'description' => 'Test',
            'type' => 'both',
            'is_active' => true,
            'is_configured' => true,
        ]);

        PermissionTriggerAssignment::create([
            'permission_id' => $permission->id,
            'permission_trigger_id' => $trigger->id,
            'event_type' => 'grant',
            'order' => 1,
            'is_enabled' => false,
            'config' => ['department_id' => '15'],
        ]);

        $matched = $this->service->matchByDepartments(['15'], ['App\\Triggers\\Bitrix24%']);

        $this->assertCount(0, $matched);
    }

    public function test_match_by_departments_returns_empty_for_empty_departments(): void
    {
        $this->createBitrixGrantAssignments('15');

        $matched = $this->service->matchByDepartments([], ['App\\Triggers\\Bitrix24%']);

        $this->assertCount(0, $matched);
    }

    public function test_get_all_managed_permission_ids_returns_unique_ids(): void
    {
        [$permissionA] = $this->createBitrixGrantAssignments('15');
        $this->createBitrixGrantAssignments('15', null, $permissionA);

        $managed = $this->service->getAllManagedPermissionIds(['App\\Triggers\\Bitrix24%']);

        $this->assertSame([$permissionA->id], $managed);
    }

    /**
     * @return array{0: Permission, 1?: Permission}
     */
    private function createBitrixGrantAssignments(
        string $firstDept,
        ?string $secondDept = null,
        ?Permission $existingPermission = null
    ): array {
        $permissionA = $existingPermission ?? Permission::create([
            'service' => 'b24',
            'name' => 'Perm ' . $firstDept,
            'description' => 'Test permission',
        ]);

        $triggerA = PermissionTrigger::create([
            'name' => 'Bitrix24 Add A ' . uniqid(),
            'class_name' => 'App\\Triggers\\Bitrix24AddToDepartmentTrigger',
            'description' => 'Test',
            'type' => 'both',
            'is_active' => true,
            'is_configured' => true,
        ]);

        PermissionTriggerAssignment::create([
            'permission_id' => $permissionA->id,
            'permission_trigger_id' => $triggerA->id,
            'event_type' => 'grant',
            'order' => 1,
            'is_enabled' => true,
            'config' => ['department_id' => $firstDept],
        ]);

        if ($secondDept === null) {
            return [$permissionA];
        }

        $permissionB = Permission::create([
            'service' => 'b24',
            'name' => 'Perm ' . $secondDept,
            'description' => 'Test permission',
        ]);

        $triggerB = PermissionTrigger::create([
            'name' => 'Bitrix24 Invite B ' . uniqid(),
            'class_name' => 'App\\Triggers\\Bitrix24InviteUserTrigger',
            'description' => 'Test',
            'type' => 'both',
            'is_active' => true,
            'is_configured' => true,
        ]);

        PermissionTriggerAssignment::create([
            'permission_id' => $permissionB->id,
            'permission_trigger_id' => $triggerB->id,
            'event_type' => 'grant',
            'order' => 2,
            'is_enabled' => true,
            'config' => ['department_id' => $secondDept],
        ]);

        return [$permissionA, $permissionB];
    }
}
