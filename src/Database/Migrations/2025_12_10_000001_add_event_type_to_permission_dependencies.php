<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('permission_dependencies', function (Blueprint $table) {
            $table->enum('event_type', ['grant', 'revoke'])
                ->default('grant')
                ->after('is_strict');
        });
    }

    public function down(): void
    {
        Schema::table('permission_dependencies', function (Blueprint $table) {
            $table->dropColumn('event_type');
        });
    }
};
