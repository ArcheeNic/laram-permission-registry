<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Triggers;

use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Triggers\BaseTrigger;
use ArcheeNic\PermissionRegistry\ValueObjects\TriggerContext;
use ArcheeNic\PermissionRegistry\ValueObjects\TriggerResult;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Mockery;

class BaseTriggerTest extends TestCase
{

    private function makeContext(array $globalFields = [], array $config = []): TriggerContext
    {
        return new TriggerContext(
            virtualUserId: 1,
            permission: Mockery::mock(Permission::class),
            permissionTriggerId: 1,
            fieldValues: [],
            globalFields: $globalFields,
            config: $config
        );
    }

    private function makeTrigger(
        array $requiredFields = [],
        ?TriggerResult $performResult = null,
        ?\Exception $performException = null,
        array $configFields = []
    ): BaseTrigger {
        return new class($requiredFields, $performResult, $performException, $configFields) extends BaseTrigger
        {
            public function __construct(
                private array $fields,
                private ?TriggerResult $result,
                private ?\Exception $exception,
                private array $configFields
            ) {}

            public function getRequiredFields(): array
            {
                return $this->fields;
            }

            public function getConfigFields(): array
            {
                return $this->configFields;
            }

            protected function perform(TriggerContext $context): TriggerResult
            {
                if ($this->exception) {
                    throw $this->exception;
                }

                return $this->result ?? TriggerResult::success();
            }

            public function canRollback(): bool
            {
                return false;
            }

            public function rollback(TriggerContext $context): void {}

            public function getName(): string
            {
                return 'test';
            }

            public function getDescription(): string
            {
                return 'test trigger';
            }
        };
    }

    public function test_execute_success_with_no_required_fields(): void
    {
        $trigger = $this->makeTrigger([], TriggerResult::success(['ok' => true]));
        $result = $trigger->execute($this->makeContext());

        $this->assertTrue($result->success);
        $this->assertArrayHasKey('ok', $result->meta);
    }

    public function test_execute_validates_required_fields(): void
    {
        $trigger = $this->makeTrigger([
            ['name' => 'email', 'required' => true, 'description' => 'Email'],
        ]);

        $result = $trigger->execute($this->makeContext([]));

        $this->assertFalse($result->success);
        $this->assertStringContainsString("'email'", $result->errorMessage);
    }

    public function test_execute_skips_internal_fields_in_validation(): void
    {
        $trigger = $this->makeTrigger([
            ['name' => 'internal_field', 'required' => true, 'is_internal' => true],
        ], TriggerResult::success());

        $result = $trigger->execute($this->makeContext([]));
        $this->assertTrue($result->success);
    }

    public function test_execute_passes_when_required_fields_provided(): void
    {
        $trigger = $this->makeTrigger([
            ['name' => 'email', 'required' => true, 'description' => 'Email'],
        ], TriggerResult::success());

        $result = $trigger->execute($this->makeContext(['email' => 'test@example.com']));
        $this->assertTrue($result->success);
    }

    public function test_execute_skips_optional_fields(): void
    {
        $trigger = $this->makeTrigger([
            ['name' => 'optional_field', 'required' => false],
        ], TriggerResult::success());

        $result = $trigger->execute($this->makeContext([]));
        $this->assertTrue($result->success);
    }

    public function test_execute_catches_exceptions_from_perform(): void
    {
        $trigger = $this->makeTrigger([], null, new \RuntimeException('Something broke'));

        $result = $trigger->execute($this->makeContext());

        $this->assertFalse($result->success);
        $this->assertSame('Something broke', $result->errorMessage);
        $this->assertArrayHasKey('exception', $result->meta);
        $this->assertSame(\RuntimeException::class, $result->meta['exception']);
    }

    public function test_validation_failure_includes_missing_and_expected_fields(): void
    {
        $trigger = $this->makeTrigger([
            ['name' => 'first_name', 'required' => true, 'description' => 'First name'],
            ['name' => 'last_name', 'required' => true, 'description' => 'Last name'],
            ['name' => 'optional', 'required' => false, 'description' => 'Optional'],
        ]);

        $result = $trigger->execute($this->makeContext(['last_name' => 'Test']));

        $this->assertFalse($result->success);
        $this->assertCount(1, $result->meta['missing_fields']);
        $this->assertSame('first_name', $result->meta['missing_fields'][0]['name']);
        $this->assertCount(3, $result->meta['expected_fields']);
    }

    public function test_internal_fields_excluded_from_meta(): void
    {
        $trigger = $this->makeTrigger([
            ['name' => 'email', 'required' => true, 'description' => 'Email'],
            ['name' => 'internal', 'required' => true, 'is_internal' => true],
        ]);

        $result = $trigger->execute($this->makeContext([]));

        $this->assertFalse($result->success);
        $fieldNames = array_column($result->meta['expected_fields'], 'name');
        $this->assertNotContains('internal', $fieldNames);
    }

    public function test_execute_validates_required_config_fields(): void
    {
        $trigger = $this->makeTrigger(
            [],
            TriggerResult::success(),
            null,
            [['name' => 'department_id', 'required' => true, 'description' => 'Department ID']]
        );

        $result = $trigger->execute($this->makeContext([], []));

        $this->assertFalse($result->success);
        $this->assertStringContainsString('department_id', $result->errorMessage);
    }

    public function test_execute_passes_when_required_config_provided(): void
    {
        $trigger = $this->makeTrigger(
            [],
            TriggerResult::success(['ok' => true]),
            null,
            [['name' => 'department_id', 'required' => true, 'description' => 'Department ID']]
        );

        $result = $trigger->execute($this->makeContext([], ['department_id' => '10']));

        $this->assertTrue($result->success);
        $this->assertArrayHasKey('ok', $result->meta);
    }

    public function test_get_config_fields_returns_empty_by_default(): void
    {
        $trigger = $this->makeTrigger([]);
        $this->assertSame([], $trigger->getConfigFields());
    }
}
