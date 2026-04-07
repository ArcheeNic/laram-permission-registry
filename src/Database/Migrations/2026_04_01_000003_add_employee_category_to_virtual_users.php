<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('virtual_users', function (Blueprint $table) {
            $table->string('employee_category')
                ->default(EmployeeCategory::STAFF->value)
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('virtual_users', function (Blueprint $table) {
            $table->dropColumn('employee_category');
        });
    }
};
