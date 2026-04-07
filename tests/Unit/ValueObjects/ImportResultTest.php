<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\ValueObjects;

use ArcheeNic\PermissionRegistry\ValueObjects\ImportResult;
use PHPUnit\Framework\TestCase;

class ImportResultTest extends TestCase
{
    public function test_success_creates_result_with_users(): void
    {
        $users = [
            ['external_id' => 'ext-1', 'fields' => ['email' => 'a@test.com', 'name' => 'Alice']],
            ['external_id' => 'ext-2', 'fields' => ['email' => 'b@test.com', 'name' => 'Bob']],
        ];

        $result = ImportResult::success($users);

        $this->assertSame($users, $result->users);
        $this->assertSame([], $result->errors);
        $this->assertFalse($result->hasErrors());
        $this->assertSame(2, $result->userCount());
    }

    public function test_success_with_empty_users(): void
    {
        $result = ImportResult::success([]);

        $this->assertSame([], $result->users);
        $this->assertFalse($result->hasErrors());
        $this->assertSame(0, $result->userCount());
    }

    public function test_failure_creates_result_with_error(): void
    {
        $result = ImportResult::failure('Connection refused');

        $this->assertSame([], $result->users);
        $this->assertSame(['Connection refused'], $result->errors);
        $this->assertTrue($result->hasErrors());
        $this->assertSame(0, $result->userCount());
    }

    public function test_partial_creates_result_with_users_and_errors(): void
    {
        $users = [
            ['external_id' => 'ext-1', 'fields' => ['email' => 'a@test.com']],
        ];
        $errors = ['Row 2: invalid email', 'Row 5: missing name'];

        $result = ImportResult::partial($users, $errors);

        $this->assertSame($users, $result->users);
        $this->assertSame($errors, $result->errors);
        $this->assertTrue($result->hasErrors());
        $this->assertSame(1, $result->userCount());
    }

    public function test_to_array_for_success(): void
    {
        $users = [
            ['external_id' => 'ext-1', 'fields' => ['email' => 'a@test.com']],
        ];

        $result = ImportResult::success($users);
        $array = $result->toArray();

        $this->assertArrayHasKey('users', $array);
        $this->assertArrayHasKey('errors', $array);
        $this->assertSame($users, $array['users']);
        $this->assertSame([], $array['errors']);
    }

    public function test_to_array_for_failure(): void
    {
        $result = ImportResult::failure('timeout');
        $array = $result->toArray();

        $this->assertSame([], $array['users']);
        $this->assertSame(['timeout'], $array['errors']);
    }

    public function test_to_array_for_partial(): void
    {
        $users = [['external_id' => 'ext-1', 'fields' => ['name' => 'Test']]];
        $errors = ['Row 3 skipped'];

        $result = ImportResult::partial($users, $errors);
        $array = $result->toArray();

        $this->assertSame($users, $array['users']);
        $this->assertSame($errors, $array['errors']);
    }

    public function test_has_errors_returns_false_for_success(): void
    {
        $result = ImportResult::success([['external_id' => '1', 'fields' => []]]);

        $this->assertFalse($result->hasErrors());
    }

    public function test_has_errors_returns_true_for_failure(): void
    {
        $result = ImportResult::failure('err');

        $this->assertTrue($result->hasErrors());
    }

    public function test_user_count_returns_correct_count(): void
    {
        $users = [
            ['external_id' => '1', 'fields' => []],
            ['external_id' => '2', 'fields' => []],
            ['external_id' => '3', 'fields' => []],
        ];

        $result = ImportResult::success($users);

        $this->assertSame(3, $result->userCount());
    }
}
