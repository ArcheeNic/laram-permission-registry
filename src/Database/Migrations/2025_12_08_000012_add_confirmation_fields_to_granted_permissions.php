<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('granted_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('requested_by')->nullable()->after('status_message');
            $table->unsignedBigInteger('confirmed_by')->nullable()->after('requested_by');
            $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');
        });
    }

    public function down(): void
    {
        Schema::table('granted_permissions', function (Blueprint $table) {
            $table->dropColumn(['requested_by', 'confirmed_by', 'confirmed_at']);
        });
    }
};
