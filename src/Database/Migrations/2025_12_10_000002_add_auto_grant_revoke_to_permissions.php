<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->boolean('auto_grant')->default(false)->after('tags');
            $table->boolean('auto_revoke')->default(false)->after('auto_grant');
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn(['auto_grant', 'auto_revoke']);
        });
    }
};
