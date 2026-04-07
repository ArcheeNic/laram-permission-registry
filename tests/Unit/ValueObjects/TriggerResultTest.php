<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\ValueObjects;

use ArcheeNic\PermissionRegistry\ValueObjects\TriggerResult;
use PHPUnit\Framework\TestCase;

class TriggerResultTest extends TestCase
{
    public function test_success_creates_successful_result(): void
    {
        $result = TriggerResult::success(['key' => 'val']);

        $this->assertTrue($result->success);
        $this->assertNull($result->errorMessage);
        $this->assertSame(['key' => 'val'], $result->meta);
    }

    public function test_success_with_empty_meta(): void
    {
        $result = TriggerResult::success();

        $this->assertTrue($result->success);
        $this->assertSame([], $result->meta);
    }

    public function test_failure_creates_failed_result(): void
    {
        $result = TriggerResult::failure('Something went wrong', ['detail' => 1]);

        $this->assertFalse($result->success);
        $this->assertSame('Something went wrong', $result->errorMessage);
        $this->assertSame(['detail' => 1], $result->meta);
    }

    public function test_failure_with_empty_meta(): void
    {
        $result = TriggerResult::failure('err');

        $this->assertSame([], $result->meta);
    }

    public function test_awaiting_resolution_creates_failed_result_with_flag(): void
    {
        $result = TriggerResult::awaitingResolution('Need manual input', ['key' => 'val']);

        $this->assertFalse($result->success);
        $this->assertSame('Need manual input', $result->errorMessage);
        $this->assertTrue($result->awaitingResolution);
        $this->assertSame(['key' => 'val'], $result->meta);
    }

    public function test_failure_defaults_awaiting_resolution_to_false(): void
    {
        $result = TriggerResult::failure('err');

        $this->assertFalse($result->awaitingResolution);
    }

    public function test_result_is_readonly(): void
    {
        $result = TriggerResult::success();

        $reflection = new \ReflectionClass($result);
        $this->assertTrue($reflection->isReadOnly());
    }
}
