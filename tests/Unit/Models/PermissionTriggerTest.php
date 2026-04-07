<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Models;

use ArcheeNic\PermissionRegistry\Models\PermissionTrigger;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermissionTriggerTest extends TestCase
{
    public function test_can_be_created_with_fillable_attributes(): void
    {
        $trigger = PermissionTrigger::create([
            'name' => 'test-trigger',
            'class_name' => 'App\Triggers\TestTrigger',
            'description' => 'Test trigger',
            'type' => 'grant',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('permission_triggers', [
            'name' => 'test-trigger',
        ]);
        $this->assertSame('test-trigger', $trigger->name);
        $this->assertTrue($trigger->is_active);
    }

    public function test_assignments_relationship(): void
    {
        $trigger = new PermissionTrigger();
        $this->assertInstanceOf(HasMany::class, $trigger->assignments());
    }

    public function test_execution_logs_relationship(): void
    {
        $trigger = new PermissionTrigger();
        $this->assertInstanceOf(HasMany::class, $trigger->executionLogs());
    }

    public function test_field_mappings_relationship(): void
    {
        $trigger = new PermissionTrigger();
        $this->assertInstanceOf(HasMany::class, $trigger->fieldMappings());
    }

    public function test_is_active_cast_to_boolean(): void
    {
        $trigger = PermissionTrigger::create([
            'name' => 'cast-active-trigger',
            'class_name' => 'App\Triggers\CastTrigger',
            'is_active' => 1,
        ]);

        $trigger->refresh();
        $this->assertTrue($trigger->is_active);
    }
}
