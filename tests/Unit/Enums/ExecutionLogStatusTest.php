<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Enums;

use ArcheeNic\PermissionRegistry\Enums\ExecutionLogStatus;
use PHPUnit\Framework\TestCase;

class ExecutionLogStatusTest extends TestCase
{
    public function test_all_cases_exist(): void
    {
        $cases = ExecutionLogStatus::cases();
        $values = array_map(fn($c) => $c->value, $cases);

        $this->assertContains('pending', $values);
        $this->assertContains('running', $values);
        $this->assertContains('success', $values);
        $this->assertContains('failed', $values);
        $this->assertCount(4, $cases);
    }

    public function test_label_returns_string_for_every_case(): void
    {
        foreach (ExecutionLogStatus::cases() as $case) {
            $this->assertNotEmpty($case->label());
        }
    }

    public function test_specific_labels(): void
    {
        $this->assertSame('Ожидает', ExecutionLogStatus::PENDING->label());
        $this->assertSame('Выполняется', ExecutionLogStatus::RUNNING->label());
        $this->assertSame('Успешно', ExecutionLogStatus::SUCCESS->label());
        $this->assertSame('Ошибка', ExecutionLogStatus::FAILED->label());
    }
}
