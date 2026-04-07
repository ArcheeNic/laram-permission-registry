<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('permission_trigger_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_trigger_id')->constrained()->onDelete('cascade');
            $table->enum('event_type', ['grant', 'revoke']);
            $table->integer('order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();

            $table->unique(['permission_id', 'permission_trigger_id', 'event_type'], 'permission_trigger_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_trigger_assignments');
    }
};
