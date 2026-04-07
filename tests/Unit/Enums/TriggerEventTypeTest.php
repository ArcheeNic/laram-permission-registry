<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Enums;

use ArcheeNic\PermissionRegistry\Enums\TriggerEventType;
use PHPUnit\Framework\TestCase;

class TriggerEventTypeTest extends TestCase
{
    public function test_all_cases_exist(): void
    {
        $cases = TriggerEventType::cases();
        $values = array_map(fn($c) => $c->value, $cases);

        $this->assertContains('grant', $values);
        $this->assertContains('revoke', $values);
        $this->assertCount(2, $cases);
    }

    public function test_labels(): void
    {
        $this->assertSame('Выдача', TriggerEventType::GRANT->label());
        $this->assertSame('Отзыв', TriggerEventType::REVOKE->label());
    }
}
