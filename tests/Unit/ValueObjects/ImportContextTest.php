<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\ValueObjects;

use ArcheeNic\PermissionRegistry\ValueObjects\ImportContext;
use PHPUnit\Framework\TestCase;

class ImportContextTest extends TestCase
{
    public function test_constructor_sets_all_properties(): void
    {
        $context = new ImportContext(
            permissionImportId: 5,
            config: ['api_url' => 'https://example.com'],
            fieldMappingSchema: ['email' => 'user_email', 'name' => 'full_name']
        );

        $this->assertSame(5, $context->permissionImportId);
        $this->assertSame(['api_url' => 'https://example.com'], $context->config);
        $this->assertSame(['email' => 'user_email', 'name' => 'full_name'], $context->fieldMappingSchema);
    }

    public function test_is_readonly(): void
    {
        $reflection = new \ReflectionClass(ImportContext::class);
        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_properties_are_accessible(): void
    {
        $context = new ImportContext(
            permissionImportId: 1,
            config: [],
            fieldMappingSchema: []
        );

        $this->assertSame(1, $context->permissionImportId);
        $this->assertSame([], $context->config);
        $this->assertSame([], $context->fieldMappingSchema);
    }

    public function test_config_can_contain_nested_arrays(): void
    {
        $config = [
            'credentials' => ['token' => 'abc123'],
            'options' => ['timeout' => 30, 'retry' => 3],
        ];

        $context = new ImportContext(
            permissionImportId: 10,
            config: $config,
            fieldMappingSchema: []
        );

        $this->assertSame($config, $context->config);
    }
}
