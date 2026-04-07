<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('virtual_user_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_user_id')->constrained('virtual_users')->onDelete('cascade');
            $table->foreignId('permission_field_id')->constrained()->onDelete('cascade');
            $table->string('value')->nullable();
            $table->timestamps();

            $table->unique(['virtual_user_id', 'permission_field_id'], 'vufv_user_field_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_user_field_values');
    }
};
