<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Models;

use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VirtualUserTest extends TestCase
{
    public function test_can_be_created_with_fillable_attributes(): void
    {
        $user = VirtualUser::create([
            'name' => 'Test User',
            'meta' => ['key' => 'value'],
            'status' => VirtualUserStatus::ACTIVE,
        ]);

        $this->assertDatabaseHas('virtual_users', [
            'name' => 'Test User',
        ]);
        $this->assertSame('Test User', $user->name);
    }

    public function test_positions_relationship(): void
    {
        $user = new VirtualUser();
        $this->assertInstanceOf(BelongsToMany::class, $user->positions());
    }

    public function test_groups_relationship(): void
    {
        $user = new VirtualUser();
        $this->assertInstanceOf(BelongsToMany::class, $user->groups());
    }

    public function test_granted_permissions_relationship(): void
    {
        $user = new VirtualUser();
        $this->assertInstanceOf(HasMany::class, $user->grantedPermissions());
    }

    public function test_field_values_relationship(): void
    {
        $user = new VirtualUser();
        $this->assertInstanceOf(HasMany::class, $user->fieldValues());
    }

    public function test_meta_cast_to_array(): void
    {
        $user = VirtualUser::create([
            'name' => 'Meta Test',
            'meta' => ['foo' => 'bar'],
            'status' => VirtualUserStatus::ACTIVE,
        ]);

        $user->refresh();
        $this->assertIsArray($user->meta);
        $this->assertSame(['foo' => 'bar'], $user->meta);
    }
}
