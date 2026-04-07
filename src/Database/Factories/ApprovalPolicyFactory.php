<?php

namespace ArcheeNic\PermissionRegistry\Database\Factories;

use ArcheeNic\PermissionRegistry\Enums\ApprovalType;
use ArcheeNic\PermissionRegistry\Models\ApprovalPolicy;
use ArcheeNic\PermissionRegistry\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApprovalPolicyFactory extends Factory
{
    protected $model = ApprovalPolicy::class;

    public function definition(): array
    {
        return [
            'permission_id' => Permission::factory(),
            'approval_type' => ApprovalType::SINGLE->value,
            'required_count' => 1,
            'is_active' => true,
        ];
    }
}
