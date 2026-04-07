<?php

namespace ArcheeNic\PermissionRegistry\ValueObjects;

readonly class TriggerResult
{
    public function __construct(
        public bool $success,
        public ?string $errorMessage = null,
        public array $meta = [],
        public bool $awaitingResolution = false
    ) {
    }

    public static function success(array $meta = []): self
    {
        return new self(true, null, $meta);
    }

    public static function failure(string $errorMessage, array $meta = []): self
    {
        return new self(false, $errorMessage, $meta);
    }

    public static function awaitingResolution(string $errorMessage, array $meta = []): self
    {
        return new self(false, $errorMessage, $meta, true);
    }
}
