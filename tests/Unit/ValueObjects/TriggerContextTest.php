<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\ValueObjects;

use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\ValueObjects\TriggerContext;
use Mockery;
use PHPUnit\Framework\TestCase;

class TriggerContextTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_constructor_sets_all_properties(): void
    {
        $permission = Mockery::mock(Permission::class);
        $grantedPermission = Mockery::mock(GrantedPermission::class);

        $context = new TriggerContext(
            virtualUserId: 42,
            permission: $permission,
            permissionTriggerId: 7,
            fieldValues: ['a' => 'b'],
            globalFields: ['email' => 'test@example.com'],
            grantedPermission: $grantedPermission,
            config: ['department_id' => '10']
        );

        $this->assertSame(42, $context->virtualUserId);
        $this->assertSame($permission, $context->permission);
        $this->assertSame(7, $context->permissionTriggerId);
        $this->assertSame(['a' => 'b'], $context->fieldValues);
        $this->assertSame(['email' => 'test@example.com'], $context->globalFields);
        $this->assertSame($grantedPermission, $context->grantedPermission);
        $this->assertSame(['department_id' => '10'], $context->config);
    }

    public function test_config_defaults_to_empty_array(): void
    {
        $context = new TriggerContext(
            virtualUserId: 1,
            permission: Mockery::mock(Permission::class),
            permissionTriggerId: 1,
            fieldValues: [],
            globalFields: []
        );

        $this->assertSame([], $context->config);
    }

    public function test_granted_permission_defaults_to_null(): void
    {
        $context = new TriggerContext(
            virtualUserId: 1,
            permission: Mockery::mock(Permission::class),
            permissionTriggerId: 1,
            fieldValues: [],
            globalFields: []
        );

        $this->assertNull($context->grantedPermission);
    }

    public function test_is_readonly(): void
    {
        $reflection = new \ReflectionClass(TriggerContext::class);
        $this->assertTrue($reflection->isReadOnly());
    }
}
