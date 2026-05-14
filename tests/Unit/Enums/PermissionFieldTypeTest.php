<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Enums;

use ArcheeNic\PermissionRegistry\Enums\PermissionFieldType;
use PHPUnit\Framework\TestCase;

class PermissionFieldTypeTest extends TestCase
{
    public function test_email_normalizes_lowercase_and_trim(): void
    {
        $this->assertSame('alice@test.com', PermissionFieldType::EMAIL->normalize('  Alice@Test.COM '));
    }

    public function test_phone_strips_non_digits(): void
    {
        $this->assertSame('79991234567', PermissionFieldType::PHONE->normalize('+7 (999) 123-45-67'));
    }

    public function test_url_lowercases(): void
    {
        $this->assertSame('https://example.com', PermissionFieldType::URL->normalize(' HTTPS://Example.COM '));
    }

    public function test_string_only_trims(): void
    {
        $this->assertSame('Alice', PermissionFieldType::STRING->normalize(' Alice '));
    }

    public function test_null_input_returns_null(): void
    {
        $this->assertNull(PermissionFieldType::EMAIL->normalize(null));
    }

    public function test_empty_string_returns_null(): void
    {
        $this->assertNull(PermissionFieldType::EMAIL->normalize('   '));
    }

    public function test_boolean_truthy_and_falsy(): void
    {
        $this->assertSame('1', PermissionFieldType::BOOLEAN->normalize('YES'));
        $this->assertSame('1', PermissionFieldType::BOOLEAN->normalize('true'));
        $this->assertSame('1', PermissionFieldType::BOOLEAN->normalize('1'));
        $this->assertSame('0', PermissionFieldType::BOOLEAN->normalize('no'));
        $this->assertSame('0', PermissionFieldType::BOOLEAN->normalize('false'));
    }
}
