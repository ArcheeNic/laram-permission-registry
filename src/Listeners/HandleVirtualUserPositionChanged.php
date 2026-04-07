<?php

namespace ArcheeNic\PermissionRegistry\Listeners;

use ArcheeNic\PermissionRegistry\Events\VirtualUserPositionChanged;

class HandleVirtualUserPositionChanged
{
    /**
     * Обработка события изменения должности пользователя
     *
     * @param VirtualUserPositionChanged $event
     * @return void
     */
    public function handle(VirtualUserPositionChanged $event): void
    {
        // Событие оставлено для интеграций/аудита через bridge listeners.
        // Синхронизация прав выполняется в AssignVirtualUserPositionAction через ReconcileUserPermissionsAction.
    }
}
