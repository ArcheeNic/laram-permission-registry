<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('permission_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->foreignId('required_permission_id')->constrained('permissions')->onDelete('cascade');
            $table->boolean('is_strict')->default(false);
            $table->timestamps();

            $table->unique(['permission_id', 'required_permission_id'], 'permission_dependency_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_dependencies');
    }
};
