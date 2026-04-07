<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trigger_field_mappings', function (Blueprint $table) {
            $table->boolean('is_internal')->default(false)->after('global_field_name');
        });
    }

    public function down(): void
    {
        Schema::table('trigger_field_mappings', function (Blueprint $table) {
            $table->dropColumn('is_internal');
        });
    }
};
