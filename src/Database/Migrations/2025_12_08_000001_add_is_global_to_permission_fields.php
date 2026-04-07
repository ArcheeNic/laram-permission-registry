<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('permission_fields', function (Blueprint $table) {
            $table->boolean('is_global')->default(false)->after('default_value');
        });
    }

    public function down(): void
    {
        Schema::table('permission_fields', function (Blueprint $table) {
            $table->dropColumn('is_global');
        });
    }
};
