<?php

namespace ArcheeNic\PermissionRegistry\Database\Factories;

use ArcheeNic\PermissionRegistry\Enums\GrantedPermissionStatus;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class GrantedPermissionFactory extends Factory
{
    protected $model = GrantedPermission::class;

    public function definition(): array
    {
        return [
            'virtual_user_id' => VirtualUser::factory(),
            'permission_id' => Permission::factory(),
            'status' => GrantedPermissionStatus::GRANTED->value,
            'enabled' => true,
            'meta' => [],
            'granted_at' => now(),
        ];
    }

    public function awaitingApproval(): static
    {
        return $this->state(['status' => GrantedPermissionStatus::AWAITING_APPROVAL->value]);
    }
}
