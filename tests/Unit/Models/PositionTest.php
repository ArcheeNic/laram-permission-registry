<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Models;

use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PositionTest extends TestCase
{
    public function test_can_be_created_with_fillable_attributes(): void
    {
        $position = Position::create([
            'name' => 'Test Position',
            'description' => 'Test desc',
            'parent_id' => null,
        ]);

        $this->assertDatabaseHas('positions', [
            'name' => 'Test Position',
        ]);
        $this->assertSame('Test Position', $position->name);
    }

    public function test_parent_relationship(): void
    {
        $position = new Position();
        $this->assertInstanceOf(BelongsTo::class, $position->parent());
    }

    public function test_children_relationship(): void
    {
        $position = new Position();
        $this->assertInstanceOf(HasMany::class, $position->children());
    }

    public function test_permissions_relationship(): void
    {
        $position = new Position();
        $this->assertInstanceOf(BelongsToMany::class, $position->permissions());
    }

    public function test_groups_relationship(): void
    {
        $position = new Position();
        $this->assertInstanceOf(BelongsToMany::class, $position->groups());
    }

    public function test_users_relationship(): void
    {
        $position = new Position();
        $this->assertInstanceOf(BelongsToMany::class, $position->users());
    }
}
