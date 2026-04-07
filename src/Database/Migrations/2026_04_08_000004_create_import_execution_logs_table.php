<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('import_execution_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('import_run_id');
            $table->foreignId('permission_import_id')->constrained('permission_imports')->cascadeOnDelete();
            $table->string('status');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->jsonb('stats')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('import_run_id', 'import_execution_logs_run_idx');
            $table->index(['permission_import_id', 'status'], 'import_execution_logs_import_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_execution_logs');
    }
};
