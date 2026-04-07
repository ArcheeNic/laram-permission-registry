<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('import_field_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_import_id')->constrained('permission_imports')->cascadeOnDelete();
            $table->string('import_field_name');
            $table->foreignId('permission_field_id')->constrained('permission_fields')->cascadeOnDelete();
            $table->boolean('is_internal')->default(false);
            $table->timestamps();

            $table->unique(['permission_import_id', 'import_field_name'], 'import_field_mappings_import_field_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_field_mappings');
    }
};
