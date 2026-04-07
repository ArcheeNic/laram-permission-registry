<?php

namespace ArcheeNic\PermissionRegistry\Database\Factories;

use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class VirtualUserFactory extends Factory
{
    protected $model = VirtualUser::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'user_id' => null,
            'status' => VirtualUserStatus::ACTIVE,
            'employee_category' => EmployeeCategory::STAFF->value,
            'meta' => [],
        ];
    }

    public function deactivated(): static
    {
        return $this->state(['status' => VirtualUserStatus::DEACTIVATED]);
    }

    public function withUserId(int $userId): static
    {
        return $this->state(['user_id' => $userId]);
    }
}
