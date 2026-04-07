<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Services;

use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\PermissionTrigger;
use ArcheeNic\PermissionRegistry\Models\TriggerFieldMapping;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use ArcheeNic\PermissionRegistry\Services\TriggerFieldMappingService;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class TriggerFieldMappingServiceTest extends TestCase
{
    private TriggerFieldMappingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TriggerFieldMappingService();
    }

    public function test_get_mapping_returns_empty_when_no_mappings(): void
    {
        $triggerId = PermissionTrigger::create(['name' => 'TestTrigger', 'class_name' => 'App\Triggers\Test'])->id;

        $result = $this->service->getMapping($triggerId);

        $this->assertSame([], $result);
    }

    public function test_get_mapping_returns_mapping_from_db_and_caches(): void
    {
        $field = PermissionField::create(['name' => 'email']);
        $trigger = PermissionTrigger::create(['name' => 'TestTrigger', 'class_name' => 'App\Triggers\Test']);
        TriggerFieldMapping::create([
            'permission_trigger_id' => $trigger->id,
            'trigger_field_name' => 'email',
            'permission_field_id' => $field->id,
            'is_internal' => false,
        ]);

        $result = $this->service->getMapping($trigger->id);

        $this->assertArrayHasKey('email', $result);
        $this->assertEquals($field->id, $result['email']['permission_field_id']);
        $this->assertEquals('email', $result['email']['permission_field_name']);
        $this->assertEmpty($result['email']['is_internal']);

        $this->assertNotEmpty(Cache::get("trigger_field_mapping_{$trigger->id}"));
    }

    public function test_clear_cache_removes_mapping_from_cache(): void
    {
        $triggerId = 999;
        $cacheKey = "trigger_field_mapping_{$triggerId}";
        Cache::put($cacheKey, ['cached' => 'data'], 3600);

        $this->service->clearCache($triggerId);

        $this->assertNull(Cache::get($cacheKey));
    }

    public function test_apply_mapping_returns_empty_for_empty_mapping(): void
    {
        $user = VirtualUser::create(['name' => 'Test', 'status' => VirtualUserStatus::ACTIVE]);

        $result = $this->service->applyMapping($user->id, []);

        $this->assertSame([], $result);
    }

    public function test_apply_mapping_maps_external_field_from_virtual_user_field_value(): void
    {
        $user = VirtualUser::create(['name' => 'Test', 'status' => VirtualUserStatus::ACTIVE]);
        $field = PermissionField::create(['name' => 'email']);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $field->id,
            'value' => 'user@example.com',
        ]);

        $mapping = [
            'email' => [
                'permission_field_id' => $field->id,
                'permission_field_name' => 'email',
                'is_internal' => false,
            ],
        ];

        $result = $this->service->applyMapping($user->id, $mapping);

        $this->assertEquals(['email' => 'user@example.com'], $result);
    }

    public function test_apply_mapping_maps_internal_field_with_permission_field_id(): void
    {
        $user = VirtualUser::create(['name' => 'Test', 'status' => VirtualUserStatus::ACTIVE]);
        $field = PermissionField::create(['name' => 'internal_field']);

        $mapping = [
            'internal_field' => [
                'permission_field_id' => $field->id,
                'permission_field_name' => 'internal_field',
                'is_internal' => true,
            ],
        ];

        $result = $this->service->applyMapping($user->id, $mapping);

        $this->assertEquals(['internal_field' => $field->id], $result);
    }

    public function test_apply_mapping_skips_external_field_without_value(): void
    {
        $user = VirtualUser::create(['name' => 'Test', 'status' => VirtualUserStatus::ACTIVE]);
        $field = PermissionField::create(['name' => 'missing_email']);

        $mapping = [
            'email' => [
                'permission_field_id' => $field->id,
                'permission_field_name' => 'missing_email',
                'is_internal' => false,
            ],
        ];

        $result = $this->service->applyMapping($user->id, $mapping);

        $this->assertSame([], $result);
    }
}
