<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('granted_permission_id')->constrained('granted_permissions')->cascadeOnDelete();
            $table->foreignId('approval_policy_id')->constrained('approval_policies')->restrictOnDelete();
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'ar_status_created_idx');
            $table->index('granted_permission_id', 'ar_granted_permission_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
