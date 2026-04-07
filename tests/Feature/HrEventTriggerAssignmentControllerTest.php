<?php

namespace ArcheeNic\PermissionRegistry\Tests\Feature;

use App\Models\User;
use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use ArcheeNic\PermissionRegistry\Models\HrEventTriggerAssignment;
use ArcheeNic\PermissionRegistry\Models\PermissionTrigger;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Facades\Gate;

class HrEventTriggerAssignmentControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(function ($user, $ability) {
            if (is_string($ability) && str_starts_with($ability, 'permission-registry.')) {
                return true;
            }

            return null;
        });

        $this->actingAs(User::factory()->create());
    }

    public function test_store_requires_employee_category(): void
    {
        $trigger = PermissionTrigger::create([
            'name' => 'HR Trigger',
            'class_name' => \App\Triggers\RegruGrantEmailTrigger::class,
            'type' => 'both',
            'is_active' => true,
        ]);

        $response = $this->postJson(route('permission-registry::hr-triggers.store'), [
            'permission_trigger_id' => $trigger->id,
            'event_type' => 'hire',
            'order' => 1,
            'is_enabled' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_category']);
    }

    public function test_store_allows_same_trigger_for_different_categories(): void
    {
        $trigger = PermissionTrigger::create([
            'name' => 'HR Trigger',
            'class_name' => \App\Triggers\RegruGrantEmailTrigger::class,
            'type' => 'both',
            'is_active' => true,
        ]);

        $first = $this->postJson(route('permission-registry::hr-triggers.store'), [
            'permission_trigger_id' => $trigger->id,
            'event_type' => 'hire',
            'employee_category' => EmployeeCategory::STAFF->value,
            'order' => 1,
            'is_enabled' => true,
        ]);
        $second = $this->postJson(route('permission-registry::hr-triggers.store'), [
            'permission_trigger_id' => $trigger->id,
            'event_type' => 'hire',
            'employee_category' => EmployeeCategory::CONTRACTOR->value,
            'order' => 1,
            'is_enabled' => true,
        ]);

        $first->assertOk();
        $second->assertOk();
        $this->assertDatabaseHas('hr_event_trigger_assignments', [
            'event_type' => 'hire',
            'employee_category' => EmployeeCategory::STAFF->value,
            'permission_trigger_id' => $trigger->id,
        ]);
        $this->assertDatabaseHas('hr_event_trigger_assignments', [
            'event_type' => 'hire',
            'employee_category' => EmployeeCategory::CONTRACTOR->value,
            'permission_trigger_id' => $trigger->id,
        ]);
    }

    public function test_store_blocks_duplicate_trigger_within_same_event_and_category(): void
    {
        $trigger = PermissionTrigger::create([
            'name' => 'HR Trigger',
            'class_name' => \App\Triggers\RegruGrantEmailTrigger::class,
            'type' => 'both',
            'is_active' => true,
        ]);

        HrEventTriggerAssignment::create([
            'event_type' => 'hire',
            'employee_category' => EmployeeCategory::STAFF->value,
            'permission_trigger_id' => $trigger->id,
            'order' => 10,
            'is_enabled' => true,
        ]);

        $response = $this->postJson(route('permission-registry::hr-triggers.store'), [
            'permission_trigger_id' => $trigger->id,
            'event_type' => 'hire',
            'employee_category' => EmployeeCategory::STAFF->value,
            'order' => 20,
            'is_enabled' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['permission_trigger_id']);
    }
}
