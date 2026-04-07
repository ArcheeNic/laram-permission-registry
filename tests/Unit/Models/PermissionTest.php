<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Models;

use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermissionTest extends TestCase
{
    public function test_can_be_created_with_fillable_attributes(): void
    {
        $permission = Permission::create([
            'service' => 'test',
            'name' => 'test-permission',
            'description' => 'Test desc',
            'tags' => ['tag1', 'tag2'],
            'auto_grant' => true,
            'auto_revoke' => false,
        ]);

        $this->assertDatabaseHas('permissions', [
            'service' => 'test',
            'name' => 'test-permission',
        ]);
        $this->assertSame('test', $permission->service);
        $this->assertSame('test-permission', $permission->name);
        $this->assertTrue($permission->auto_grant);
        $this->assertFalse($permission->auto_revoke);
    }

    public function test_fields_relationship(): void
    {
        $permission = new Permission();
        $this->assertInstanceOf(BelongsToMany::class, $permission->fields());
    }

    public function test_groups_relationship(): void
    {
        $permission = new Permission();
        $this->assertInstanceOf(BelongsToMany::class, $permission->groups());
    }

    public function test_positions_relationship(): void
    {
        $permission = new Permission();
        $this->assertInstanceOf(BelongsToMany::class, $permission->positions());
    }

    public function test_trigger_assignments_relationship(): void
    {
        $permission = new Permission();
        $this->assertInstanceOf(HasMany::class, $permission->triggerAssignments());
    }

    public function test_grant_triggers_relationship(): void
    {
        $permission = new Permission();
        $this->assertInstanceOf(HasMany::class, $permission->grantTriggers());
    }

    public function test_revoke_triggers_relationship(): void
    {
        $permission = new Permission();
        $this->assertInstanceOf(HasMany::class, $permission->revokeTriggers());
    }

    public function test_dependencies_relationship(): void
    {
        $permission = new Permission();
        $this->assertInstanceOf(HasMany::class, $permission->dependencies());
    }

    public function test_dependents_relationship(): void
    {
        $permission = new Permission();
        $this->assertInstanceOf(HasMany::class, $permission->dependents());
    }

    public function test_grant_dependencies_relationship(): void
    {
        $permission = new Permission();
        $this->assertInstanceOf(HasMany::class, $permission->grantDependencies());
    }

    public function test_revoke_dependencies_relationship(): void
    {
        $permission = new Permission();
        $this->assertInstanceOf(HasMany::class, $permission->revokeDependencies());
    }

    public function test_tags_cast_to_array(): void
    {
        $permission = Permission::create([
            'service' => 'test',
            'name' => 'cast-test',
            'tags' => ['a', 'b'],
        ]);

        $permission->refresh();
        $this->assertIsArray($permission->tags);
        $this->assertSame(['a', 'b'], $permission->tags);
    }
}
