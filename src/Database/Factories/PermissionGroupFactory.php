<?php

namespace ArcheeNic\PermissionRegistry\Database\Factories;

use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionGroupFactory extends Factory
{
    protected $model = PermissionGroup::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'description' => $this->faker->sentence(),
        ];
    }
}
