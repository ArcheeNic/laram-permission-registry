<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('permission_execution_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('granted_permission_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_trigger_id')->constrained()->onDelete('cascade');
            $table->enum('event_type', ['grant', 'revoke']);
            $table->enum('status', ['pending', 'running', 'success', 'failed'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_execution_logs');
    }
};
