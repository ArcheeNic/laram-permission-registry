<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Events;

use ArcheeNic\PermissionRegistry\Events\VirtualUserGroupChanged;
use PHPUnit\Framework\TestCase;

class VirtualUserGroupChangedTest extends TestCase
{
    public function test_constructor_sets_properties(): void
    {
        $event = new VirtualUserGroupChanged(1, 10, true);

        $this->assertSame(1, $event->userId);
        $this->assertSame(10, $event->groupId);
        $this->assertTrue($event->added);
    }

    public function test_added_false(): void
    {
        $event = new VirtualUserGroupChanged(5, 20, false);

        $this->assertSame(5, $event->userId);
        $this->assertSame(20, $event->groupId);
        $this->assertFalse($event->added);
    }

    public function test_properties_are_readonly(): void
    {
        $event = new VirtualUserGroupChanged(1, 10, true);

        $this->expectException(\Error::class);
        $event->groupId = 2;
    }
}
