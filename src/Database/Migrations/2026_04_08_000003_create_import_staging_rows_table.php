<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('import_staging_rows', function (Blueprint $table) {
            $table->id();
            $table->uuid('import_run_id');
            $table->foreignId('permission_import_id')->constrained('permission_imports')->cascadeOnDelete();
            $table->string('external_id');
            $table->jsonb('fields');
            $table->string('match_status');
            $table->foreignId('matched_virtual_user_id')->nullable()->constrained('virtual_users')->nullOnDelete();
            $table->boolean('is_approved')->nullable()->default(null);
            $table->timestamps();

            $table->index('import_run_id', 'import_staging_rows_run_idx');
            $table->index(['import_run_id', 'match_status'], 'import_staging_rows_run_status_idx');
            $table->index(['import_run_id', 'is_approved'], 'import_staging_rows_run_approved_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_staging_rows');
    }
};
