<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('granted_permissions', function (Blueprint $table) {
            $table->foreignId('resource_id')
                ->nullable()
                ->after('permission_id')
                ->constrained('permission_resources')
                ->nullOnDelete();
            $table->string('resource_name_at_grant')->nullable()->after('resource_id');
            $table->string('source', 20)->default('manual')->after('resource_name_at_grant');

            $table->index(['virtual_user_id', 'permission_id', 'resource_id'], 'granted_permissions_user_perm_resource_idx');
        });
    }

    public function down(): void
    {
        Schema::table('granted_permissions', function (Blueprint $table) {
            $table->dropIndex('granted_permissions_user_perm_resource_idx');
            $table->dropForeign(['resource_id']);
            $table->dropColumn(['resource_id', 'resource_name_at_grant', 'source']);
        });
    }
};
