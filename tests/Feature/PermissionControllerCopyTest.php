<?php

namespace ArcheeNic\PermissionRegistry\Tests\Feature;

use App\Models\User;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Facades\Gate;

class PermissionControllerCopyTest extends TestCase
{
    public function test_copy_endpoint_creates_copy_and_redirects_to_edit(): void
    {
        Gate::before(function ($user, $ability) {
            if (is_string($ability) && str_starts_with($ability, 'permission-registry.')) {
                return true;
            }

            return null;
        });

        $this->actingAs(User::factory()->create());

        $source = Permission::factory()->create([
            'service' => 'hr',
            'name' => 'salary-read',
        ]);

        $response = $this->post(route('permission-registry::permissions.copy', $source));

        $copy = Permission::query()
            ->where('service', $source->service)
            ->where('id', '!=', $source->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($copy);
        $response->assertRedirect(route('permission-registry::permissions.edit', $copy));
        $response->assertSessionHas('success');
    }

    public function test_copy_endpoint_returns_forbidden_without_manage_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $source = Permission::factory()->create();

        $this->post(route('permission-registry::permissions.copy', $source))
            ->assertForbidden();
    }
}
