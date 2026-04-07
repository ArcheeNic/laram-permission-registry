<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('virtual_users', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'vu_status_created_at_idx');
            $table->index(['employee_category', 'created_at'], 'vu_category_created_at_idx');
        });

        Schema::table('virtual_user_groups', function (Blueprint $table) {
            $table->index(['permission_group_id', 'virtual_user_id'], 'vug_group_user_idx');
        });
    }

    public function down(): void
    {
        Schema::table('virtual_users', function (Blueprint $table) {
            $table->dropIndex('vu_status_created_at_idx');
            $table->dropIndex('vu_category_created_at_idx');
        });

        Schema::table('virtual_user_groups', function (Blueprint $table) {
            $table->dropIndex('vug_group_user_idx');
        });
    }
};
