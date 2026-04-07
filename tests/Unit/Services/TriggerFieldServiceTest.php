<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Services;

use ArcheeNic\PermissionRegistry\Actions\GetVirtualUserMappedFieldsAction;
use ArcheeNic\PermissionRegistry\Services\TriggerFieldService;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TriggerFieldServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_load_calls_action_and_caches_result(): void
    {
        $action = Mockery::mock(GetVirtualUserMappedFieldsAction::class);
        $expected = new Collection(['email' => ['value' => 'test@example.com', 'id' => 1]]);
        $action->shouldReceive('execute')
            ->once()
            ->with(1, 10, [true, false])
            ->andReturn($expected);

        $service = new TriggerFieldService($action);
        $result = $service->load(1, 10, [true, false]);

        $this->assertSame($expected, $result);
        $this->assertSame($expected, $service->getMappedFields(1, 10));
    }

    public function test_reload_throws_runtime_exception_if_not_loaded_first(): void
    {
        $action = Mockery::mock(GetVirtualUserMappedFieldsAction::class);
        $action->shouldNotReceive('execute');

        $service = new TriggerFieldService($action);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Невозможно перезагрузить поля');

        $service->reload([true, false]);
    }

    public function test_reload_works_after_load(): void
    {
        $action = Mockery::mock(GetVirtualUserMappedFieldsAction::class);
        $first = new Collection(['email' => ['value' => 'old', 'id' => 1]]);
        $second = new Collection(['email' => ['value' => 'new', 'id' => 1]]);

        $action->shouldReceive('execute')
            ->with(1, 10, [true, false])
            ->andReturn($first);
        $action->shouldReceive('execute')
            ->with(1, 10, [true])
            ->andReturn($second);

        $service = new TriggerFieldService($action);
        $service->load(1, 10, [true, false]);
        $result = $service->reload([true]);

        $this->assertEquals('new', $result['email']['value']);
    }

    public function test_get_mapped_fields_uses_cache_when_same_user_and_trigger(): void
    {
        $action = Mockery::mock(GetVirtualUserMappedFieldsAction::class);
        $cached = new Collection(['email' => ['value' => 'cached@test.com', 'id' => 1]]);
        $action->shouldReceive('execute')
            ->once()
            ->with(1, 10, [true, false])
            ->andReturn($cached);

        $service = new TriggerFieldService($action);
        $first = $service->getMappedFields(1, 10, [true, false]);
        $second = $service->getMappedFields(1, 10, [true, false]);

        $this->assertSame($first, $second);
    }

    public function test_get_mapped_fields_reloads_when_different_user_or_trigger(): void
    {
        $action = Mockery::mock(GetVirtualUserMappedFieldsAction::class);
        $forUser1 = new Collection(['email' => ['value' => 'user1@test.com', 'id' => 1]]);
        $forUser2 = new Collection(['email' => ['value' => 'user2@test.com', 'id' => 1]]);

        $action->shouldReceive('execute')
            ->with(1, 10, [true, false])
            ->andReturn($forUser1);
        $action->shouldReceive('execute')
            ->with(2, 10, [true, false])
            ->andReturn($forUser2);

        $service = new TriggerFieldService($action);
        $service->getMappedFields(1, 10);
        $result = $service->getMappedFields(2, 10);

        $this->assertEquals('user2@test.com', $result['email']['value']);
    }

    public function test_get_required_field_from_collection_returns_field_data(): void
    {
        $action = Mockery::mock(GetVirtualUserMappedFieldsAction::class);
        $service = new TriggerFieldService($action);
        $collection = new Collection(['email' => ['value' => 'test@example.com', 'id' => 42]]);

        $result = $service->getRequiredFieldFromCollection($collection, 'email');

        $this->assertEquals(['value' => 'test@example.com', 'id' => 42], $result);
    }

    public function test_get_required_field_from_collection_throws_runtime_exception_for_missing_field(): void
    {
        $action = Mockery::mock(GetVirtualUserMappedFieldsAction::class);
        $service = new TriggerFieldService($action);
        $collection = new Collection([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Поле 'missing_field' не найдено");

        $service->getRequiredFieldFromCollection($collection, 'missing_field');
    }

    public function test_get_required_field_id_from_collection_returns_id(): void
    {
        $action = Mockery::mock(GetVirtualUserMappedFieldsAction::class);
        $service = new TriggerFieldService($action);
        $collection = new Collection(['email' => ['value' => 'test@example.com', 'id' => 99]]);

        $result = $service->getRequiredFieldIdFromCollection($collection, 'email');

        $this->assertSame(99, $result);
    }

    public function test_get_required_field_value_from_collection_returns_value(): void
    {
        $action = Mockery::mock(GetVirtualUserMappedFieldsAction::class);
        $service = new TriggerFieldService($action);
        $collection = new Collection(['email' => ['value' => 'value@test.com', 'id' => 1]]);

        $result = $service->getRequiredFieldValueFromCollection($collection, 'email');

        $this->assertSame('value@test.com', $result);
    }

    public function test_get_field_value_from_collection_returns_null_for_missing_field(): void
    {
        $action = Mockery::mock(GetVirtualUserMappedFieldsAction::class);
        $service = new TriggerFieldService($action);
        $collection = new Collection(['other' => ['value' => 'x', 'id' => 1]]);

        $result = $service->getFieldValueFromCollection($collection, 'missing');

        $this->assertNull($result);
    }

    public function test_get_field_value_from_collection_returns_value_when_field_exists(): void
    {
        $action = Mockery::mock(GetVirtualUserMappedFieldsAction::class);
        $service = new TriggerFieldService($action);
        $collection = new Collection(['email' => ['value' => 'present@test.com', 'id' => 1]]);

        $result = $service->getFieldValueFromCollection($collection, 'email');

        $this->assertSame('present@test.com', $result);
    }
}
