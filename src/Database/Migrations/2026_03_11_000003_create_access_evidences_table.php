<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('granted_permission_id')->constrained('granted_permissions')->cascadeOnDelete();
            $table->unsignedBigInteger('manual_provision_task_id')->nullable();
            $table->string('type', 30);
            $table->text('value');
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('provided_by')->nullable();
            $table->timestamps();

            $table->foreign('manual_provision_task_id')
                ->references('id')
                ->on('manual_provision_tasks')
                ->nullOnDelete();

            $table->index('granted_permission_id', 'ae_granted_permission_idx');
            $table->index('manual_provision_task_id', 'ae_manual_task_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_evidences');
    }
};
