<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use ArcheeNic\PermissionRegistry\Enums\PermissionFieldType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('permission_fields', function (Blueprint $table) {
            $table->string('type', 20)->default(PermissionFieldType::STRING->value)->after('name');
        });

        DB::table('permission_fields')->orderBy('id')->each(function (object $row): void {
            $type = $this->guessType((string) $row->name);

            if ($type !== PermissionFieldType::STRING) {
                DB::table('permission_fields')
                    ->where('id', $row->id)
                    ->update(['type' => $type->value]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('permission_fields', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    private function guessType(string $name): PermissionFieldType
    {
        $normalized = mb_strtolower($name);

        if (str_contains($normalized, 'email') || str_contains($normalized, 'e-mail') || str_contains($normalized, 'почт')) {
            return PermissionFieldType::EMAIL;
        }

        if (str_contains($normalized, 'phone') || str_contains($normalized, 'mobile') || str_contains($normalized, 'тел') || str_contains($normalized, 'мобиль')) {
            return PermissionFieldType::PHONE;
        }

        if (str_contains($normalized, 'url') || str_contains($normalized, 'ссылк') || str_contains($normalized, 'link')) {
            return PermissionFieldType::URL;
        }

        if (str_contains($normalized, 'дата') || str_contains($normalized, 'date') || str_contains($normalized, 'birthday')) {
            return PermissionFieldType::DATE;
        }

        return PermissionFieldType::STRING;
    }
};
