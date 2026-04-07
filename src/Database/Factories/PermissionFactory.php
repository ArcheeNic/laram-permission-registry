<?php

namespace ArcheeNic\PermissionRegistry\Database\Factories;

use ArcheeNic\PermissionRegistry\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        return [
            'service' => $this->faker->randomElement(['bitrix24', 'jira', 'slack', 'telegram', 'bitbucket', 'regru', 'permission-registry']),
            'name' => $this->faker->unique()->slug(2),
            'description' => $this->faker->sentence(),
            'tags' => [],
            'auto_grant' => false,
            'auto_revoke' => false,
        ];
    }
}
