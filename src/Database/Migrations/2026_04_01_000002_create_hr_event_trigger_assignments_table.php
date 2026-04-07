<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hr_event_trigger_assignments', function (Blueprint $table) {
            $table->id();
            $table->enum('event_type', ['hire', 'fire']);
            $table->foreignId('permission_trigger_id')->constrained()->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'is_enabled', 'order'], 'hr_event_trigger_assignments_event_order_idx');
            $table->unique(['event_type', 'permission_trigger_id'], 'hr_event_trigger_assignments_unique_event_trigger');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_event_trigger_assignments');
    }
};
