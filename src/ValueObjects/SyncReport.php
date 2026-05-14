<?php

namespace ArcheeNic\PermissionRegistry\ValueObjects;

readonly class SyncReport
{
    public function __construct(
        public string $service,
        public string $kind,
        public int $created = 0,
        public int $updated = 0,
        public int $disappeared = 0,
        public int $reappeared = 0,
        public array $errors = [],
    ) {}

    public function toArray(): array
    {
        return [
            'service' => $this->service,
            'kind' => $this->kind,
            'created' => $this->created,
            'updated' => $this->updated,
            'disappeared' => $this->disappeared,
            'reappeared' => $this->reappeared,
            'errors' => $this->errors,
        ];
    }
}
