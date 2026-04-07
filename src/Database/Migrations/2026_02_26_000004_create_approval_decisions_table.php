<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('approval_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->cascadeOnDelete();
            $table->unsignedBigInteger('approver_id');
            $table->string('decision', 20);
            $table->text('comment')->nullable();
            $table->timestamp('decided_at');
            $table->timestamps();

            $table->unique(['approval_request_id', 'approver_id'], 'ad_request_approver_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_decisions');
    }
};
