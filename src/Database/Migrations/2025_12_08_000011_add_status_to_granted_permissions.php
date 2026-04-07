<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('granted_permissions', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'granting',
                'granted',
                'revoking',
                'revoked',
                'failed',
                'partially_granted'
            ])->default('granted')->after('permission_id');
            $table->text('status_message')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('granted_permissions', function (Blueprint $table) {
            $table->dropColumn(['status', 'status_message']);
        });
    }
};
