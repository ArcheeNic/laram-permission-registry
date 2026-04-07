<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trigger_field_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_trigger_id')
                ->constrained('permission_triggers')
                ->onDelete('cascade');
            $table->string('trigger_field_name');
            $table->string('global_field_name');
            $table->timestamps();

            $table->unique(['permission_trigger_id', 'trigger_field_name'], 'trigger_field_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trigger_field_mappings');
    }
};

