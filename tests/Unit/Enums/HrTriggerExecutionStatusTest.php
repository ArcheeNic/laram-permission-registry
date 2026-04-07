<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Enums;

use ArcheeNic\PermissionRegistry\Enums\HrTriggerExecutionStatus;
use PHPUnit\Framework\TestCase;

class HrTriggerExecutionStatusTest extends TestCase
{
    public function test_all_cases_exist(): void
    {
        $cases = HrTriggerExecutionStatus::cases();
        $values = array_map(fn ($c) => $c->value, $cases);

        $this->assertContains('pending', $values);
        $this->assertContains('running', $values);
        $this->assertContains('success', $values);
        $this->assertContains('failed', $values);
        $this->assertContains('awaiting_resolution', $values);
        $this->assertCount(5, $cases);
    }
}
