<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Services;

use ArcheeNic\PermissionRegistry\Services\ImportDiscoveryService;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Facades\Config;

class ImportDiscoveryServiceTest extends TestCase
{
    private ImportDiscoveryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImportDiscoveryService();
    }

    public function test_discover_returns_empty_array_when_namespace_config_is_missing(): void
    {
        Config::set('imports.namespace', null);
        Config::set('imports.directory', app_path('Imports'));

        $result = $this->service->discover();

        $this->assertSame([], $result);
    }

    public function test_discover_returns_empty_array_when_directory_config_is_missing(): void
    {
        Config::set('imports.namespace', 'App\\Imports');
        Config::set('imports.directory', null);

        $result = $this->service->discover();

        $this->assertSame([], $result);
    }

    public function test_discover_returns_empty_array_when_directory_does_not_exist(): void
    {
        Config::set('imports.namespace', 'App\\Imports');
        Config::set('imports.directory', '/non/existent/path/' . uniqid());

        $result = $this->service->discover();

        $this->assertSame([], $result);
    }

    public function test_discover_returns_array_with_expected_metadata_keys(): void
    {
        $tempDir = sys_get_temp_dir() . '/import_test_' . uniqid();
        mkdir($tempDir, 0755, true);

        $namespace = 'ImportTestNs' . uniqid();
        $className = 'TestImporter';
        $fqcn = $namespace . '\\' . $className;

        $code = <<<PHP
<?php
namespace {$namespace};
use ArcheeNic\PermissionRegistry\Contracts\PermissionImportInterface;
use ArcheeNic\PermissionRegistry\ValueObjects\ImportContext;
use ArcheeNic\PermissionRegistry\ValueObjects\ImportResult;

class {$className} implements PermissionImportInterface
{
    public function execute(ImportContext \$context): ImportResult
    {
        return ImportResult::success([]);
    }

    public function getName(): string { return 'Test Importer'; }
    public function getDescription(): string { return 'A test importer'; }
    public function getRequiredFields(): array { return ['email']; }
    public function getConfigFields(): array { return ['api_key']; }
    public function getRelatedTriggerClassPatterns(): array { return ['App\\\\Triggers\\\\Bitrix24%']; }
    public function getDepartmentFieldName(): string { return 'department_ids'; }
}
PHP;

        file_put_contents($tempDir . '/' . $className . '.php', $code);
        require_once $tempDir . '/' . $className . '.php';

        Config::set('imports.namespace', $namespace);
        Config::set('imports.directory', $tempDir);

        $result = $this->service->discover();

        $this->assertNotEmpty($result);
        $this->assertSame($fqcn, $result[0]['class_name']);
        $this->assertSame('Test Importer', $result[0]['name']);
        $this->assertSame('A test importer', $result[0]['description']);
        $this->assertSame(['email'], $result[0]['required_fields']);
        $this->assertSame(['api_key'], $result[0]['config_fields']);

        @unlink($tempDir . '/' . $className . '.php');
        @rmdir($tempDir);
    }

    public function test_discover_skips_non_implementing_classes(): void
    {
        $tempDir = sys_get_temp_dir() . '/import_test_' . uniqid();
        mkdir($tempDir, 0755, true);

        $namespace = 'ImportTestNs' . uniqid();
        $className = 'NotAnImporter';

        $code = <<<PHP
<?php
namespace {$namespace};

class {$className}
{
    public function doSomething(): void {}
}
PHP;

        file_put_contents($tempDir . '/' . $className . '.php', $code);
        require_once $tempDir . '/' . $className . '.php';

        Config::set('imports.namespace', $namespace);
        Config::set('imports.directory', $tempDir);

        $result = $this->service->discover();

        $this->assertSame([], $result);

        @unlink($tempDir . '/' . $className . '.php');
        @rmdir($tempDir);
    }
}
