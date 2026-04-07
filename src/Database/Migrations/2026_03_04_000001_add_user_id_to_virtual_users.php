<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('virtual_users', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->unique()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('virtual_users', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
