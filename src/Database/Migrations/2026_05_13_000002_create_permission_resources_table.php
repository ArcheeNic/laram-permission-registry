<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_resources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('service');
            $table->string('kind');
            $table->string('external_id');
            $table->string('name');
            $table->json('metadata')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->boolean('present_in_source')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['service', 'kind']);
            $table->index('present_in_source');
            $table->unique(
                ['tenant_id', 'service', 'kind', 'external_id'],
                'permission_resources_unique'
            )->whereNull('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_resources');
    }
};
