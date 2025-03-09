<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('service');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->unique(['service', 'name']);
        });

        Schema::create('permission_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('default_value')->nullable();
            $table->timestamps();
        });

        Schema::create('permission_permission_field', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_field_id')->constrained()->onDelete('cascade');
            $table->primary(['permission_id', 'permission_field_id']);
        });

        Schema::create('permission_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permission_permission_group', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_group_id')->constrained()->onDelete('cascade');
            $table->primary(['permission_id', 'permission_group_id']);
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('positions')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('position_permission', function (Blueprint $table) {
            $table->foreignId('position_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->primary(['position_id', 'permission_id']);
        });

        Schema::create('position_permission_group', function (Blueprint $table) {
            $table->foreignId('position_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_group_id')->constrained()->onDelete('cascade');
            $table->primary(['position_id', 'permission_group_id']);
        });

        Schema::create('granted_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->boolean('enabled')->default(true);
            $table->json('meta')->nullable();
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'permission_id']);
        });

        Schema::create('granted_permission_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('granted_permission_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_field_id')->constrained()->onDelete('cascade');
            $table->string('value')->nullable();
            $table->timestamps();

            $table->unique(['granted_permission_id', 'permission_field_id']);
        });

        Schema::create('user_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_group_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'permission_group_id']);
        });

        Schema::create('user_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('position_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'position_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_positions');
        Schema::dropIfExists('user_groups');
        Schema::dropIfExists('granted_permission_field_values');
        Schema::dropIfExists('granted_permissions');
        Schema::dropIfExists('position_permission_group');
        Schema::dropIfExists('position_permission');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('permission_permission_group');
        Schema::dropIfExists('permission_groups');
        Schema::dropIfExists('permission_permission_field');
        Schema::dropIfExists('permission_fields');
        Schema::dropIfExists('permissions');
    }
};
