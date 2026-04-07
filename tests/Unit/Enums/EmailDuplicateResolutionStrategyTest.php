<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Enums;

use ArcheeNic\PermissionRegistry\Enums\EmailDuplicateResolutionStrategy;
use PHPUnit\Framework\TestCase;

class EmailDuplicateResolutionStrategyTest extends TestCase
{
    public function test_all_cases_exist(): void
    {
        $cases = EmailDuplicateResolutionStrategy::cases();
        $values = array_map(fn ($c) => $c->value, $cases);

        $this->assertContains('auto_increment', $values);
        $this->assertContains('manual', $values);
        $this->assertCount(2, $cases);
    }
}
