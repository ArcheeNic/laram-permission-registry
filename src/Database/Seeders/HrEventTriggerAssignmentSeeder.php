<?php

namespace ArcheeNic\PermissionRegistry\Database\Seeders;

use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use ArcheeNic\PermissionRegistry\Models\HrEventTriggerAssignment;
use Illuminate\Database\Seeder;

class HrEventTriggerAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $staffAssignments = HrEventTriggerAssignment::query()
            ->where('employee_category', EmployeeCategory::STAFF->value)
            ->get();

        foreach ($staffAssignments as $assignment) {
            HrEventTriggerAssignment::query()->firstOrCreate([
                HrEventTriggerAssignment::EVENT_TYPE => $assignment->event_type,
                HrEventTriggerAssignment::EMPLOYEE_CATEGORY => EmployeeCategory::CONTRACTOR->value,
                HrEventTriggerAssignment::PERMISSION_TRIGGER_ID => $assignment->permission_trigger_id,
            ], [
                HrEventTriggerAssignment::ORDER => $assignment->order,
                HrEventTriggerAssignment::IS_ENABLED => $assignment->is_enabled,
                HrEventTriggerAssignment::CONFIG => $assignment->config,
            ]);
        }
    }
}
