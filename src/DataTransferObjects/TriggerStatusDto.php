<?php

namespace ArcheeNic\PermissionRegistry\DataTransferObjects;

use Carbon\Carbon;

class TriggerStatusDto
{
    public function __construct(
        public int $triggerId,
        public string $triggerName,
        public string $status, // pending, running, success, failed
        public ?string $errorMessage = null,
        public ?Carbon $startedAt = null,
        public ?Carbon $completedAt = null,
        public ?array $meta = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'trigger_id' => $this->triggerId,
            'name' => $this->triggerName,
            'status' => $this->status,
            'error_message' => $this->errorMessage,
            'started_at' => $this->startedAt?->toIso8601String(),
            'completed_at' => $this->completedAt?->toIso8601String(),
            'meta' => $this->meta,
        ];
    }
}
