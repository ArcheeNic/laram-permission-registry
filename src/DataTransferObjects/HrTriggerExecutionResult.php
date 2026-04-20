<?php

namespace ArcheeNic\PermissionRegistry\DataTransferObjects;

class HrTriggerExecutionResult
{
    public function __construct(
        public bool $success,
        public ?int $failedLogId = null,
        public ?string $errorMessage = null,
        public ?string $triggerName = null,
        public ?int $permissionTriggerId = null,
        public bool $awaitingResolution = false,
        public bool $crashed = false,
    ) {
    }

    public static function ok(): self
    {
        return new self(success: true);
    }

    public static function failed(
        int $logId,
        ?string $errorMessage,
        ?string $triggerName,
        ?int $permissionTriggerId,
        bool $awaitingResolution = false,
    ): self {
        return new self(
            success: false,
            failedLogId: $logId,
            errorMessage: $errorMessage,
            triggerName: $triggerName,
            permissionTriggerId: $permissionTriggerId,
            awaitingResolution: $awaitingResolution,
        );
    }

    public static function crashed(
        int $logId,
        string $errorMessage,
        ?string $triggerName,
        ?int $permissionTriggerId,
    ): self {
        return new self(
            success: false,
            failedLogId: $logId,
            errorMessage: $errorMessage,
            triggerName: $triggerName,
            permissionTriggerId: $permissionTriggerId,
            crashed: true,
        );
    }
}
