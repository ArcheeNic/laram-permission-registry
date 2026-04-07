<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const STATUSES = "'awaiting_approval','pending','granting','granted','revoking','revoked','failed','partially_granted','partially_revoked','rejected','manual_pending','declared'";

    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('CREATE TABLE granted_permissions_tmp AS SELECT * FROM granted_permissions');
            Schema::drop('granted_permissions');

            Schema::create('granted_permissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('virtual_user_id');
                $table->foreignId('permission_id')->constrained('permissions');
                $table->enum('status', [
                    'awaiting_approval', 'pending', 'granting', 'granted',
                    'revoking', 'revoked', 'failed', 'partially_granted',
                    'partially_revoked', 'rejected', 'manual_pending', 'declared',
                ])->default('granted');
                $table->text('status_message')->nullable();
                $table->unsignedBigInteger('requested_by')->nullable();
                $table->unsignedBigInteger('confirmed_by')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->boolean('enabled')->default(true);
                $table->json('meta')->nullable();
                $table->timestamp('granted_at')->useCurrent();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });

            DB::statement('INSERT INTO granted_permissions SELECT * FROM granted_permissions_tmp');
            DB::statement('DROP TABLE granted_permissions_tmp');
        } else {
            DB::statement('ALTER TABLE granted_permissions DROP CONSTRAINT IF EXISTS granted_permissions_status_check');
            DB::statement(
                'ALTER TABLE granted_permissions ADD CONSTRAINT granted_permissions_status_check CHECK (status IN ('.self::STATUSES.'))'
            );
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'sqlite') {
            DB::statement('ALTER TABLE granted_permissions DROP CONSTRAINT IF EXISTS granted_permissions_status_check');
            DB::statement(
                "ALTER TABLE granted_permissions ADD CONSTRAINT granted_permissions_status_check CHECK (status IN ('awaiting_approval','pending','granting','granted','revoking','revoked','failed','partially_granted','partially_revoked','rejected'))"
            );
        }
    }
};
