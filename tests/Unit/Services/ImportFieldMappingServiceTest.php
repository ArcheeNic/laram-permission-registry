<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Services;

use ArcheeNic\PermissionRegistry\Models\ImportFieldMapping;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\PermissionImport;
use ArcheeNic\PermissionRegistry\Services\ImportFieldMappingService;
use ArcheeNic\PermissionRegistry\Tests\TestCase;

class ImportFieldMappingServiceTest extends TestCase
{
    private ImportFieldMappingService $service;

    private PermissionImport $import;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImportFieldMappingService();

        $this->import = PermissionImport::create([
            'name' => 'Test Import',
            'class_name' => 'App\\Imports\\TestImporter',
            'description' => 'Test',
            'is_active' => true,
        ]);
    }

    public function test_get_mapping_returns_configured_field_mapping(): void
    {
        $emailField = PermissionField::create(['name' => 'email', 'is_global' => true]);
        $nameField = PermissionField::create(['name' => 'full_name', 'is_global' => true]);

        ImportFieldMapping::create([
            'permission_import_id' => $this->import->id,
            'import_field_name' => 'user_email',
            'permission_field_id' => $emailField->id,
            'is_internal' => false,
        ]);

        ImportFieldMapping::create([
            'permission_import_id' => $this->import->id,
            'import_field_name' => 'display_name',
            'permission_field_id' => $nameField->id,
            'is_internal' => false,
        ]);

        $mapping = $this->service->getMapping($this->import->id);

        $this->assertArrayHasKey('user_email', $mapping);
        $this->assertArrayHasKey('display_name', $mapping);
        $this->assertSame($emailField->id, $mapping['user_email']['permission_field_id']);
        $this->assertSame($nameField->id, $mapping['display_name']['permission_field_id']);
    }

    public function test_get_mapping_returns_empty_when_no_mappings_exist(): void
    {
        $mapping = $this->service->getMapping($this->import->id);

        $this->assertSame([], $mapping);
    }

    public function test_apply_mapping_transforms_external_fields_correctly(): void
    {
        $emailField = PermissionField::create(['name' => 'email', 'is_global' => true]);
        $nameField = PermissionField::create(['name' => 'full_name', 'is_global' => true]);

        $mapping = [
            'user_email' => ['permission_field_id' => $emailField->id, 'is_internal' => false],
            'display_name' => ['permission_field_id' => $nameField->id, 'is_internal' => false],
        ];

        $externalFields = [
            'user_email' => 'alice@example.com',
            'display_name' => 'Alice Smith',
            'extra_field' => 'ignored',
        ];

        $result = $this->service->applyMapping($externalFields, $mapping);

        $this->assertSame($emailField->id, array_key_first($result));
        $this->assertSame('alice@example.com', $result[$emailField->id]);
        $this->assertSame('Alice Smith', $result[$nameField->id]);
        $this->assertCount(2, $result);
    }

    public function test_apply_mapping_returns_empty_for_empty_mapping(): void
    {
        $externalFields = ['user_email' => 'test@test.com'];

        $result = $this->service->applyMapping($externalFields, []);

        $this->assertSame([], $result);
    }

    public function test_apply_mapping_skips_fields_not_present_in_external_data(): void
    {
        $emailField = PermissionField::create(['name' => 'email', 'is_global' => true]);

        $mapping = [
            'user_email' => ['permission_field_id' => $emailField->id, 'is_internal' => false],
        ];

        $externalFields = ['other_field' => 'value'];

        $result = $this->service->applyMapping($externalFields, $mapping);

        $this->assertSame([], $result);
    }

    public function test_apply_mapping_handles_internal_fields(): void
    {
        $internalField = PermissionField::create(['name' => 'department_id', 'is_global' => true]);

        $mapping = [
            'dept' => ['permission_field_id' => $internalField->id, 'is_internal' => true],
        ];

        $externalFields = ['dept' => '42'];

        $result = $this->service->applyMapping($externalFields, $mapping);

        $this->assertArrayHasKey($internalField->id, $result);
    }
}
