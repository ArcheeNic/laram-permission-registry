<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Support;

use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Support\DefaultUserToVirtualUserResolver;
use ArcheeNic\PermissionRegistry\Tests\TestCase;

class DefaultUserToVirtualUserResolverTest extends TestCase
{
    public function test_resolve_returns_virtual_user_id_when_user_linked(): void
    {
        $virtualUser = VirtualUser::create(['name' => 'Test', 'user_id' => 42, 'status' => VirtualUserStatus::ACTIVE]);
        $resolver = new DefaultUserToVirtualUserResolver();

        $this->assertSame($virtualUser->id, $resolver->resolve(42));
    }

    public function test_resolve_returns_null_when_no_virtual_user_for_user_id(): void
    {
        $resolver = new DefaultUserToVirtualUserResolver();

        $this->assertNull($resolver->resolve(999));
    }

    public function test_resolve_returns_null_when_user_id_not_linked(): void
    {
        VirtualUser::create(['name' => 'No link', 'status' => VirtualUserStatus::ACTIVE]);
        $resolver = new DefaultUserToVirtualUserResolver();

        $this->assertNull($resolver->resolve(1));
    }
}
