<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Шаг 1: Добавить permission_field_id как nullable
        Schema::table('trigger_field_mappings', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_field_id')->nullable()->after('trigger_field_name');
        });

        // Шаг 2: Конвертировать данные - найти ID по имени
        $mappings = DB::table('trigger_field_mappings')->get();
        
        foreach ($mappings as $mapping) {
            $field = DB::table('permission_fields')
                ->where('name', $mapping->global_field_name)
                ->first();
            
            if ($field) {
                DB::table('trigger_field_mappings')
                    ->where('id', $mapping->id)
                    ->update(['permission_field_id' => $field->id]);
            }
        }

        // Шаг 3: Удалить записи без соответствующего поля
        DB::table('trigger_field_mappings')
            ->whereNull('permission_field_id')
            ->delete();

        // Шаг 4: Сделать permission_field_id NOT NULL и добавить индекс
        Schema::table('trigger_field_mappings', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_field_id')->nullable(false)->change();
        });

        // Шаг 5: Добавить foreign key constraint
        Schema::table('trigger_field_mappings', function (Blueprint $table) {
            $table->foreign('permission_field_id')
                ->references('id')
                ->on('permission_fields')
                ->onDelete('cascade');
        });

        // Шаг 6: Удалить колонку global_field_name
        Schema::table('trigger_field_mappings', function (Blueprint $table) {
            $table->dropColumn('global_field_name');
        });
    }

    public function down(): void
    {
        // Добавить обратно global_field_name
        Schema::table('trigger_field_mappings', function (Blueprint $table) {
            $table->string('global_field_name')->after('trigger_field_name');
        });

        // Восстановить данные - записать имя поля из permission_fields
        $mappings = DB::table('trigger_field_mappings')->get();
        
        foreach ($mappings as $mapping) {
            $field = DB::table('permission_fields')
                ->where('id', $mapping->permission_field_id)
                ->first();
            
            if ($field) {
                DB::table('trigger_field_mappings')
                    ->where('id', $mapping->id)
                    ->update(['global_field_name' => $field->name]);
            }
        }

        // Удалить foreign key и колонку permission_field_id
        Schema::table('trigger_field_mappings', function (Blueprint $table) {
            $table->dropForeign(['permission_field_id']);
            $table->dropColumn('permission_field_id');
        });
    }
};

