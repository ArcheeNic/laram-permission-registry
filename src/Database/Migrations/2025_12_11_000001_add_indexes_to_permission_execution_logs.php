<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('permission_execution_logs', function (Blueprint $table) {
            // Составной индекс для поиска последнего лога по праву и типу события
            // Используется в RetryTriggerFromFailedAction для определения eventType
            $table->index(
                ['granted_permission_id', 'event_type', 'created_at'],
                'pel_gp_event_created_idx'
            );

            // Индекс для поиска упавших триггеров
            // Используется в ContinuePermissionGrantingAction для fallback поиска
            $table->index(
                ['granted_permission_id', 'status'],
                'pel_gp_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('permission_execution_logs', function (Blueprint $table) {
            $table->dropIndex('pel_gp_event_created_idx');
            $table->dropIndex('pel_gp_status_idx');
        });
    }
};
