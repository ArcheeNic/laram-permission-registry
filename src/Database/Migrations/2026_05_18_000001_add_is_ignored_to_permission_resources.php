<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permission_resources', function (Blueprint $table) {
            $table->boolean('is_ignored')->default(false)->after('present_in_source');
            $table->index('is_ignored');
        });
    }

    public function down(): void
    {
        Schema::table('permission_resources', function (Blueprint $table) {
            $table->dropIndex(['is_ignored']);
            $table->dropColumn('is_ignored');
        });
    }
};
