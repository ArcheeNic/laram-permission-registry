<?php

namespace ArcheeNic\PermissionRegistry\Database\Factories;

use ArcheeNic\PermissionRegistry\Models\PermissionField;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFieldFactory extends Factory
{
    protected $model = PermissionField::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'default_value' => null,
            'is_global' => false,
            'required_on_user_create' => false,
        ];
    }

    public function global(): static
    {
        return $this->state(['is_global' => true]);
    }
}
