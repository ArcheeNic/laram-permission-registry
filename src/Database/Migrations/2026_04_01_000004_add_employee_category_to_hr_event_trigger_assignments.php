<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hr_event_trigger_assignments', function (Blueprint $table) {
            $table->string('employee_category')
                ->default(EmployeeCategory::STAFF->value)
                ->after('event_type');

            $table->dropUnique('hr_event_trigger_assignments_unique_event_trigger');
            $table->dropIndex('hr_event_trigger_assignments_event_order_idx');
        });

        $rows = DB::table('hr_event_trigger_assignments')
            ->select(['event_type', 'permission_trigger_id', 'order', 'is_enabled', 'config', 'created_at', 'updated_at'])
            ->get()
            ->map(static fn (object $row): array => [
                'event_type' => $row->event_type,
                'employee_category' => EmployeeCategory::CONTRACTOR->value,
                'permission_trigger_id' => $row->permission_trigger_id,
                'order' => $row->order,
                'is_enabled' => $row->is_enabled,
                'config' => $row->config,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ])
            ->all();

        if ($rows !== []) {
            DB::table('hr_event_trigger_assignments')->insert($rows);
        }

        Schema::table('hr_event_trigger_assignments', function (Blueprint $table) {
            $table->index(
                ['event_type', 'employee_category', 'is_enabled', 'order'],
                'hr_event_trigger_assignments_event_category_order_idx'
            );
            $table->unique(
                ['event_type', 'employee_category', 'permission_trigger_id'],
                'hr_event_trigger_assignments_unique_event_category_trigger'
            );
        });
    }

    public function down(): void
    {
        DB::table('hr_event_trigger_assignments')
            ->where('employee_category', EmployeeCategory::CONTRACTOR->value)
            ->delete();

        Schema::table('hr_event_trigger_assignments', function (Blueprint $table) {
            $table->dropUnique('hr_event_trigger_assignments_unique_event_category_trigger');
            $table->dropIndex('hr_event_trigger_assignments_event_category_order_idx');
            $table->dropColumn('employee_category');

            $table->index(
                ['event_type', 'is_enabled', 'order'],
                'hr_event_trigger_assignments_event_order_idx'
            );
            $table->unique(
                ['event_type', 'permission_trigger_id'],
                'hr_event_trigger_assignments_unique_event_trigger'
            );
        });
    }
};
