<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Events;

use ArcheeNic\PermissionRegistry\Events\VirtualUserPositionChanged;
use PHPUnit\Framework\TestCase;

class VirtualUserPositionChangedTest extends TestCase
{
    public function test_constructor_sets_properties(): void
    {
        $event = new VirtualUserPositionChanged(1, 10, 5);

        $this->assertSame(1, $event->userId);
        $this->assertSame(10, $event->positionId);
        $this->assertSame(5, $event->oldPositionId);
    }

    public function test_old_position_id_defaults_to_null(): void
    {
        $event = new VirtualUserPositionChanged(1, 10);

        $this->assertSame(1, $event->userId);
        $this->assertSame(10, $event->positionId);
        $this->assertNull($event->oldPositionId);
    }

    public function test_properties_are_readonly(): void
    {
        $event = new VirtualUserPositionChanged(1, 10);

        $this->expectException(\Error::class);
        $event->userId = 2;
    }
}
