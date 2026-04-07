<?php

namespace ArcheeNic\PermissionRegistry\Database\Seeders;

use ArcheeNic\PermissionRegistry\Models\Permission;
use Illuminate\Database\Seeder;

class SystemPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['service' => 'permission-registry', 'name' => 'manage', 'description' => 'Full access to Permission Registry admin'],
            ['service' => 'permission-registry', 'name' => 'approve', 'description' => 'Approve/reject permission requests'],
            ['service' => 'permission-registry', 'name' => 'self-service', 'description' => 'View own permissions and request new ones'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['service' => $perm['service'], 'name' => $perm['name']],
                ['description' => $perm['description']]
            );
        }
    }
}
