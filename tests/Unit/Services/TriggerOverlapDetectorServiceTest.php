<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Services;

use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionTrigger;
use ArcheeNic\PermissionRegistry\Models\PermissionTriggerAssignment;
use ArcheeNic\PermissionRegistry\Services\TriggerOverlapDetectorService;
use ArcheeNic\PermissionRegistry\Tests\TestCase;

class TriggerOverlapDetectorServiceTest extends TestCase
{
    private TriggerOverlapDetectorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TriggerOverlapDetectorService::class);
    }

    public function test_detect_overlaps_returns_department_with_multiple_permissions(): void
    {
        $first = $this->createPermissionWithAssignment('First', '660');
        $second = $this->createPermissionWithAssignment('Second', '660');

        $overlaps = $this->service->detectOverlaps();

        $this->assertArrayHasKey('660', $overlaps);
        $actual = array_column($overlaps['660'], 'permission_id');
        sort($actual);
        $expected = [$first->id, $second->id];
        sort($expected);
        $this->assertSame($expected, $actual);
    }

    public function test_detect_overlaps_returns_empty_when_no_overlap(): void
    {
        $this->createPermissionWithAssignment('First', '15');
        $this->createPermissionWithAssignment('Second', '412');

        $overlaps = $this->service->detectOverlaps();

        $this->assertSame([], $overlaps);
    }

    public function test_detect_overlaps_ignores_disabled_assignments(): void
    {
        $this->createPermissionWithAssignment('First', '660');
        $this->createPermissionWithAssignment('Second', '660', false);

        $overlaps = $this->service->detectOverlaps();

        $this->assertSame([], $overlaps);
    }

    public function test_detect_overlaps_filters_by_permission_id(): void
    {
        $first = $this->createPermissionWithAssignment('First', '660');
        $this->createPermissionWithAssignment('Second', '660');
        $this->createPermissionWithAssignment('Third', '15');

        $overlaps = $this->service->detectOverlaps($first->id);

        $this->assertArrayHasKey('660', $overlaps);
        $this->assertCount(2, $overlaps['660']);
        $this->assertTrue(in_array($first->id, array_column($overlaps['660'], 'permission_id'), true));
    }

    private function createPermissionWithAssignment(string $name, string $departmentId, bool $enabled = true): Permission
    {
        $permission = Permission::create([
            'service' => 'b24',
            'name' => $name,
            'description' => 'Overlap test',
        ]);

        $trigger = PermissionTrigger::create([
            'name' => 'Bitrix Trigger ' . $name,
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
            'is_enabled' => $enabled,
            'config' => ['department_id' => $departmentId],
        ]);

        return $permission;
    }
}
