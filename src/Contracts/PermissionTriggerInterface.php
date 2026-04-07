<?php

namespace ArcheeNic\PermissionRegistry\Contracts;

use ArcheeNic\PermissionRegistry\ValueObjects\TriggerContext;
use ArcheeNic\PermissionRegistry\ValueObjects\TriggerResult;

interface PermissionTriggerInterface
{
    /**
     * Выполнить триггер
     */
    public function execute(TriggerContext $context): TriggerResult;

    /**
     * Может ли триггер откатить свои действия
     */
    public function canRollback(): bool;

    /**
     * Откатить действия триггера
     */
    public function rollback(TriggerContext $context): void;
}
