<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('virtual_user_field_values', function (Blueprint $table) {
            $table->enum('source', ['manual', 'trigger'])->default('manual')->after('value');
            $table->unsignedBigInteger('created_by')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('virtual_user_field_values', function (Blueprint $table) {
            $table->dropColumn(['source', 'created_by']);
        });
    }
};
