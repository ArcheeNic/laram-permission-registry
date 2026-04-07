<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // granted_permissions
        Schema::table('granted_permissions', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'permission_id']);
            $table->dropForeign(['user_id']);
        });
        
        Schema::table('granted_permissions', function (Blueprint $table) {
            $table->renameColumn('user_id', 'virtual_user_id');
        });
        
        Schema::table('granted_permissions', function (Blueprint $table) {
            $table->foreign('virtual_user_id')->references('id')->on('virtual_users')->onDelete('cascade');
            $table->unique(['virtual_user_id', 'permission_id']);
        });

        // user_groups (not yet renamed to virtual_user_groups — that's migration 000006)
        Schema::table('user_groups', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'permission_group_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('user_groups', function (Blueprint $table) {
            $table->renameColumn('user_id', 'virtual_user_id');
        });

        Schema::table('user_groups', function (Blueprint $table) {
            $table->foreign('virtual_user_id')->references('id')->on('virtual_users')->onDelete('cascade');
            $table->unique(['virtual_user_id', 'permission_group_id']);
        });

        // user_positions (not yet renamed to virtual_user_positions — that's migration 000006)
        Schema::table('user_positions', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'position_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('user_positions', function (Blueprint $table) {
            $table->renameColumn('user_id', 'virtual_user_id');
        });

        Schema::table('user_positions', function (Blueprint $table) {
            $table->foreign('virtual_user_id')->references('id')->on('virtual_users')->onDelete('cascade');
            $table->unique(['virtual_user_id', 'position_id']);
        });
    }

    public function down(): void
    {
        // granted_permissions
        Schema::table('granted_permissions', function (Blueprint $table) {
            $table->dropUnique(['virtual_user_id', 'permission_id']);
            $table->dropForeign(['virtual_user_id']);
        });
        
        Schema::table('granted_permissions', function (Blueprint $table) {
            $table->renameColumn('virtual_user_id', 'user_id');
        });
        
        Schema::table('granted_permissions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'permission_id']);
        });

        // user_groups (migration 006 already rolled back the table rename)
        Schema::table('user_groups', function (Blueprint $table) {
            $table->dropUnique(['virtual_user_id', 'permission_group_id']);
            $table->dropForeign(['virtual_user_id']);
        });

        Schema::table('user_groups', function (Blueprint $table) {
            $table->renameColumn('virtual_user_id', 'user_id');
        });

        Schema::table('user_groups', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'permission_group_id']);
        });

        // user_positions (migration 006 already rolled back the table rename)
        Schema::table('user_positions', function (Blueprint $table) {
            $table->dropUnique(['virtual_user_id', 'position_id']);
            $table->dropForeign(['virtual_user_id']);
        });

        Schema::table('user_positions', function (Blueprint $table) {
            $table->renameColumn('virtual_user_id', 'user_id');
        });

        Schema::table('user_positions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'position_id']);
        });
    }
};
