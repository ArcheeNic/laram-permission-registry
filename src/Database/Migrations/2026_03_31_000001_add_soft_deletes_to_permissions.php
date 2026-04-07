<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique('permissions_service_name_unique');
            $table->softDeletes();
            $table->unique(['service', 'name'], 'permissions_service_name_active_unique')->whereNull('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique('permissions_service_name_active_unique');
            $table->dropSoftDeletes();
            $table->unique(['service', 'name']);
        });
    }
};
