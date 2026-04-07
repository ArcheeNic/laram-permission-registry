<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('management_mode', 20)->default('automated')->after('auto_revoke');
            $table->string('risk_level', 20)->default('low')->after('management_mode');
            $table->unsignedBigInteger('system_owner_virtual_user_id')->nullable()->after('risk_level');
            $table->unsignedInteger('attestation_period_days')->nullable()->after('system_owner_virtual_user_id');

            $table->foreign('system_owner_virtual_user_id')
                ->references('id')
                ->on('virtual_users')
                ->nullOnDelete();

            $table->index('management_mode', 'permissions_management_mode_idx');
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign(['system_owner_virtual_user_id']);
            $table->dropIndex('permissions_management_mode_idx');
            $table->dropColumn([
                'management_mode',
                'risk_level',
                'system_owner_virtual_user_id',
                'attestation_period_days',
            ]);
        });
    }
};
