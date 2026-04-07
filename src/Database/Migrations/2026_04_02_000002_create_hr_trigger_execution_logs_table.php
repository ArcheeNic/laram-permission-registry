<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hr_trigger_execution_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_user_id')->constrained('virtual_users')->onDelete('cascade');
            $table->foreignId('hr_event_trigger_assignment_id')
                ->nullable()
                ->constrained('hr_event_trigger_assignments')
                ->nullOnDelete();
            $table->foreignId('permission_trigger_id')->constrained('permission_triggers')->onDelete('cascade');
            $table->enum('event_type', ['hire', 'fire']);
            $table->string('employee_category', 32)->nullable();
            $table->enum('status', ['pending', 'running', 'success', 'failed', 'awaiting_resolution'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->json('resolution_context')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['virtual_user_id', 'status'], 'htel_vu_status_idx');
            $table->index(['virtual_user_id', 'permission_trigger_id', 'created_at'], 'htel_vu_trigger_created_idx');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "CREATE INDEX htel_failed_partial_idx ON hr_trigger_execution_logs (status, created_at) WHERE status IN ('failed', 'awaiting_resolution')"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS htel_failed_partial_idx');
        }

        Schema::dropIfExists('hr_trigger_execution_logs');
    }
};
