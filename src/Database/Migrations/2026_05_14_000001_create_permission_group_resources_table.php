<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_group_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_group_id')->constrained('permission_groups')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained('permission_resources')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['permission_group_id', 'permission_id', 'resource_id'],
                'permission_group_resources_unique'
            );
            $table->index(['permission_id', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_group_resources');
    }
};
