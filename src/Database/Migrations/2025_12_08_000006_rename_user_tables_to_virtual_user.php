<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // user_groups -> virtual_user_groups
        Schema::table('user_groups', function (Blueprint $table) {
            $table->dropUnique(['virtual_user_id', 'permission_group_id']);
            $table->dropForeign(['virtual_user_id']);
            $table->dropForeign(['permission_group_id']);
        });
        
        Schema::rename('user_groups', 'virtual_user_groups');
        
        Schema::table('virtual_user_groups', function (Blueprint $table) {
            $table->foreign('virtual_user_id')->references('id')->on('virtual_users')->onDelete('cascade');
            $table->foreign('permission_group_id')->references('id')->on('permission_groups')->onDelete('cascade');
            $table->unique(['virtual_user_id', 'permission_group_id']);
        });

        // user_positions -> virtual_user_positions
        Schema::table('user_positions', function (Blueprint $table) {
            $table->dropUnique(['virtual_user_id', 'position_id']);
            $table->dropForeign(['virtual_user_id']);
            $table->dropForeign(['position_id']);
        });
        
        Schema::rename('user_positions', 'virtual_user_positions');
        
        Schema::table('virtual_user_positions', function (Blueprint $table) {
            $table->foreign('virtual_user_id')->references('id')->on('virtual_users')->onDelete('cascade');
            $table->foreign('position_id')->references('id')->on('positions')->onDelete('cascade');
            $table->unique(['virtual_user_id', 'position_id']);
        });
    }

    public function down(): void
    {
        // virtual_user_groups -> user_groups
        Schema::table('virtual_user_groups', function (Blueprint $table) {
            $table->dropUnique(['virtual_user_id', 'permission_group_id']);
            $table->dropForeign(['virtual_user_id']);
            $table->dropForeign(['permission_group_id']);
        });
        
        Schema::rename('virtual_user_groups', 'user_groups');
        
        Schema::table('user_groups', function (Blueprint $table) {
            $table->foreign('virtual_user_id')->references('id')->on('virtual_users')->onDelete('cascade');
            $table->foreign('permission_group_id')->references('id')->on('permission_groups')->onDelete('cascade');
            $table->unique(['virtual_user_id', 'permission_group_id']);
        });

        // virtual_user_positions -> user_positions
        Schema::table('virtual_user_positions', function (Blueprint $table) {
            $table->dropUnique(['virtual_user_id', 'position_id']);
            $table->dropForeign(['virtual_user_id']);
            $table->dropForeign(['position_id']);
        });
        
        Schema::rename('virtual_user_positions', 'user_positions');
        
        Schema::table('user_positions', function (Blueprint $table) {
            $table->foreign('virtual_user_id')->references('id')->on('virtual_users')->onDelete('cascade');
            $table->foreign('position_id')->references('id')->on('positions')->onDelete('cascade');
            $table->unique(['virtual_user_id', 'position_id']);
        });
    }
};
