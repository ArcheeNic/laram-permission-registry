<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Services;

use ArcheeNic\PermissionRegistry\Models\PermissionImport;
use ArcheeNic\PermissionRegistry\Services\ImportTriggerConfigResolver;
use ArcheeNic\PermissionRegistry\Tests\TestCase;

class ImportTriggerConfigResolverTest extends TestCase
{
    public function test_resolve_returns_defaults_for_missing_class(): void
    {
        $resolver = app(ImportTriggerConfigResolver::class);
        $import = PermissionImport::create([
            'name' => 'Missing Class',
            'class_name' => 'App\\Imports\\DoesNotExist',
            'description' => 'Test',
            'is_active' => true,
        ]);

        [$patterns, $departmentField] = $resolver->resolve($import);

        $this->assertSame(['App\\Triggers\\Bitrix24%'], $patterns);
        $this->assertSame('department_ids', $departmentField);
    }

    public function test_resolve_uses_importer_specific_values(): void
    {
        $resolver = app(ImportTriggerConfigResolver::class);
        $import = PermissionImport::create([
            'name' => 'Bitrix',
            'class_name' => \App\Imports\Bitrix24Import::class,
            'description' => 'Test',
            'is_active' => true,
        ]);

        [$patterns, $departmentField] = $resolver->resolve($import);

        $this->assertSame(['App\\Triggers\\Bitrix24%'], $patterns);
        $this->assertSame('department_ids', $departmentField);
    }
}
