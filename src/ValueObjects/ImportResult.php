<?php

namespace ArcheeNic\PermissionRegistry\ValueObjects;

readonly class ImportResult
{
    public function __construct(
        public array $users,
        public array $errors = [],
    ) {
    }

    public static function success(array $users): self
    {
        return new self($users);
    }

    public static function failure(string $error): self
    {
        return new self([], [$error]);
    }

    public static function partial(array $users, array $errors): self
    {
        return new self($users, $errors);
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    public function userCount(): int
    {
        return count($this->users);
    }

    public function toArray(): array
    {
        return [
            'users' => $this->users,
            'errors' => $this->errors,
        ];
    }
}
