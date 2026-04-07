<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Enums;

use ArcheeNic\PermissionRegistry\Enums\GrantedPermissionStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use ArcheeNic\PermissionRegistry\Tests\TestCase;

class GrantedPermissionStatusTest extends TestCase
{
    public function test_all_cases_exist(): void
    {
        $cases = GrantedPermissionStatus::cases();
        $values = array_map(fn($c) => $c->value, $cases);

        $this->assertContains('pending', $values);
        $this->assertContains('granting', $values);
        $this->assertContains('granted', $values);
        $this->assertContains('revoking', $values);
        $this->assertContains('revoked', $values);
        $this->assertContains('failed', $values);
        $this->assertContains('partially_granted', $values);
        $this->assertContains('partially_revoked', $values);
        $this->assertContains('awaiting_approval', $values);
        $this->assertContains('rejected', $values);
        $this->assertContains('manual_pending', $values);
        $this->assertContains('declared', $values);
        $this->assertCount(12, $cases);
    }

    public function test_label_returns_string_for_every_case(): void
    {
        foreach (GrantedPermissionStatus::cases() as $case) {
            $this->assertNotEmpty($case->label());
        }
    }

    #[DataProvider('inProgressCasesProvider')]
    public function test_is_in_progress(GrantedPermissionStatus $status, bool $expected): void
    {
        $this->assertSame($expected, $status->isInProgress());
    }

    public static function inProgressCasesProvider(): array
    {
        return [
            [GrantedPermissionStatus::PENDING, true],
            [GrantedPermissionStatus::GRANTING, true],
            [GrantedPermissionStatus::REVOKING, true],
            [GrantedPermissionStatus::GRANTED, false],
            [GrantedPermissionStatus::REVOKED, false],
            [GrantedPermissionStatus::FAILED, false],
            [GrantedPermissionStatus::PARTIALLY_GRANTED, false],
        ];
    }

    #[DataProvider('completedCasesProvider')]
    public function test_is_completed(GrantedPermissionStatus $status, bool $expected): void
    {
        $this->assertSame($expected, $status->isCompleted());
    }

    public static function completedCasesProvider(): array
    {
        return [
            [GrantedPermissionStatus::GRANTED, true],
            [GrantedPermissionStatus::REVOKED, true],
            [GrantedPermissionStatus::PENDING, false],
            [GrantedPermissionStatus::GRANTING, false],
            [GrantedPermissionStatus::REVOKING, false],
            [GrantedPermissionStatus::FAILED, false],
            [GrantedPermissionStatus::PARTIALLY_GRANTED, false],
        ];
    }

    #[DataProvider('errorCasesProvider')]
    public function test_has_error(GrantedPermissionStatus $status, bool $expected): void
    {
        $this->assertSame($expected, $status->hasError());
    }

    public static function errorCasesProvider(): array
    {
        return [
            [GrantedPermissionStatus::FAILED, true],
            [GrantedPermissionStatus::PARTIALLY_GRANTED, true],
            [GrantedPermissionStatus::PARTIALLY_REVOKED, true],
            [GrantedPermissionStatus::PENDING, false],
            [GrantedPermissionStatus::GRANTED, false],
        ];
    }
}
