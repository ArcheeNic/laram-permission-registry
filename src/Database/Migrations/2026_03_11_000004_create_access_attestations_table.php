<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_attestations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('granted_permission_id')->constrained('granted_permissions')->cascadeOnDelete();
            $table->unsignedInteger('attestation_period_days');
            $table->timestamp('due_at');
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['status', 'due_at'], 'aa_status_due_idx');
            $table->index('granted_permission_id', 'aa_granted_permission_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_attestations');
    }
};
