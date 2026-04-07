<?php

namespace ArcheeNic\PermissionRegistry\Database\Factories;

use ArcheeNic\PermissionRegistry\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->jobTitle(),
            'description' => $this->faker->sentence(),
            'parent_id' => null,
        ];
    }
}
