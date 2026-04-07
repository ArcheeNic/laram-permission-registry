<?php

namespace ArcheeNic\PermissionRegistry\ValueObjects;

readonly class DependencyValidationResult
{
    public function __construct(
        public bool $isValid,
        public array $missingPermissions = [],
        public array $missingFields = []
    ) {
    }

    public static function valid(): self
    {
        return new self(true);
    }

    public static function invalid(array $missingPermissions = [], array $missingFields = []): self
    {
        return new self(false, $missingPermissions, $missingFields);
    }

    public function getErrorMessage(): string
    {
        $messages = [];

        if (!empty($this->missingPermissions)) {
            $permissionNames = implode(', ', array_column($this->missingPermissions, 'name'));
            $messages[] = "Требуются права: {$permissionNames}";
        }

        if (!empty($this->missingFields)) {
            $fieldNames = implode(', ', array_column($this->missingFields, 'name'));
            $messages[] = "Не заполнены поля: {$fieldNames}";
        }

        return implode('. ', $messages);
    }
}
