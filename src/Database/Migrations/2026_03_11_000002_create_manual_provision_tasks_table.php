<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_provision_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('granted_permission_id')->constrained('granted_permissions')->cascadeOnDelete();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'due_at'], 'mpt_status_due_idx');
            $table->index('granted_permission_id', 'mpt_granted_permission_idx');
            $table->index('assigned_to', 'mpt_assigned_to_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_provision_tasks');
    }
};
