<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\ValueObjects;

use ArcheeNic\PermissionRegistry\ValueObjects\DependencyValidationResult;
use PHPUnit\Framework\TestCase;

class DependencyValidationResultTest extends TestCase
{
    public function test_valid_creates_valid_result(): void
    {
        $result = DependencyValidationResult::valid();

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->missingPermissions);
        $this->assertEmpty($result->missingFields);
    }

    public function test_invalid_with_missing_permissions(): void
    {
        $missing = [['id' => 1, 'name' => 'perm1']];
        $result = DependencyValidationResult::invalid($missing);

        $this->assertFalse($result->isValid);
        $this->assertCount(1, $result->missingPermissions);
        $this->assertEmpty($result->missingFields);
    }

    public function test_invalid_with_missing_fields(): void
    {
        $fields = [['id' => 5, 'name' => 'email']];
        $result = DependencyValidationResult::invalid([], $fields);

        $this->assertFalse($result->isValid);
        $this->assertEmpty($result->missingPermissions);
        $this->assertCount(1, $result->missingFields);
    }

    public function test_error_message_with_missing_permissions(): void
    {
        $result = DependencyValidationResult::invalid([
            ['name' => 'bitrix24'],
            ['name' => 'email'],
        ]);

        $this->assertStringContainsString('bitrix24', $result->getErrorMessage());
        $this->assertStringContainsString('email', $result->getErrorMessage());
        $this->assertStringContainsString('Требуются права', $result->getErrorMessage());
    }

    public function test_error_message_with_missing_fields(): void
    {
        $result = DependencyValidationResult::invalid([], [
            ['name' => 'first_name'],
        ]);

        $this->assertStringContainsString('first_name', $result->getErrorMessage());
        $this->assertStringContainsString('Не заполнены поля', $result->getErrorMessage());
    }

    public function test_error_message_with_both(): void
    {
        $result = DependencyValidationResult::invalid(
            [['name' => 'perm1']],
            [['name' => 'field1']]
        );

        $msg = $result->getErrorMessage();
        $this->assertStringContainsString('Требуются права', $msg);
        $this->assertStringContainsString('Не заполнены поля', $msg);
    }

    public function test_error_message_empty_when_valid(): void
    {
        $result = DependencyValidationResult::valid();
        $this->assertSame('', $result->getErrorMessage());
    }

    public function test_is_readonly(): void
    {
        $reflection = new \ReflectionClass(DependencyValidationResult::class);
        $this->assertTrue($reflection->isReadOnly());
    }
}
