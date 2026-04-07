<?php

namespace ArcheeNic\PermissionRegistry\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('approval_policy_approvers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_policy_id')->constrained('approval_policies')->cascadeOnDelete();
            $table->string('approver_type', 30);
            $table->unsignedBigInteger('approver_id');
            $table->timestamps();

            $table->unique(['approval_policy_id', 'approver_type', 'approver_id'], 'apa_policy_type_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_policy_approvers');
    }
};
