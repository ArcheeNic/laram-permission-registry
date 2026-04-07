<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\DataTransferObjects;

use ArcheeNic\PermissionRegistry\DataTransferObjects\TriggerStatusDto;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class TriggerStatusDtoTest extends TestCase
{
    public function test_constructor_sets_all_properties(): void
    {
        $started = Carbon::parse('2025-01-01 10:00:00');
        $completed = Carbon::parse('2025-01-01 10:00:05');

        $dto = new TriggerStatusDto(
            triggerId: 1,
            triggerName: 'Test Trigger',
            status: 'success',
            errorMessage: null,
            startedAt: $started,
            completedAt: $completed,
            meta: ['key' => 'val']
        );

        $this->assertSame(1, $dto->triggerId);
        $this->assertSame('Test Trigger', $dto->triggerName);
        $this->assertSame('success', $dto->status);
        $this->assertNull($dto->errorMessage);
        $this->assertSame($started, $dto->startedAt);
        $this->assertSame($completed, $dto->completedAt);
        $this->assertSame(['key' => 'val'], $dto->meta);
    }

    public function test_to_array_format(): void
    {
        $started = Carbon::parse('2025-01-01 10:00:00');
        $completed = Carbon::parse('2025-01-01 10:00:05');

        $dto = new TriggerStatusDto(
            triggerId: 5,
            triggerName: 'Email',
            status: 'failed',
            errorMessage: 'Connection refused',
            startedAt: $started,
            completedAt: $completed,
            meta: ['detail' => 1]
        );

        $array = $dto->toArray();

        $this->assertSame(5, $array['trigger_id']);
        $this->assertSame('Email', $array['name']);
        $this->assertSame('failed', $array['status']);
        $this->assertSame('Connection refused', $array['error_message']);
        $this->assertSame($started->toIso8601String(), $array['started_at']);
        $this->assertSame($completed->toIso8601String(), $array['completed_at']);
        $this->assertSame(['detail' => 1], $array['meta']);
    }

    public function test_to_array_with_null_dates(): void
    {
        $dto = new TriggerStatusDto(
            triggerId: 1,
            triggerName: 'Test',
            status: 'pending'
        );

        $array = $dto->toArray();

        $this->assertNull($array['started_at']);
        $this->assertNull($array['completed_at']);
        $this->assertNull($array['error_message']);
        $this->assertNull($array['meta']);
    }
}
