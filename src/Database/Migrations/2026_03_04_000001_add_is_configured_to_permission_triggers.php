<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('permission_triggers', function (Blueprint $table) {
            $table->boolean('is_configured')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('permission_triggers', function (Blueprint $table) {
            $table->dropColumn('is_configured');
        });
    }
};
