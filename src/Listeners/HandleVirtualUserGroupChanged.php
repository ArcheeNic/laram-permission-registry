<?php

namespace ArcheeNic\PermissionRegistry\Listeners;

use ArcheeNic\PermissionRegistry\Actions\AutoGrantPermissionsForGroupAction;
use ArcheeNic\PermissionRegistry\Actions\AutoRevokePermissionsForGroupAction;
use ArcheeNic\PermissionRegistry\Events\VirtualUserGroupChanged;
use Illuminate\Support\Facades\Log;

class HandleVirtualUserGroupChanged
{
    public function __construct(
        private AutoGrantPermissionsForGroupAction $autoGrantAction,
        private AutoRevokePermissionsForGroupAction $autoRevokeAction
    ) {
    }

    /**
     * Обработка события изменения группы пользователя
     *
     * @param VirtualUserGroupChanged $event
     * @return void
     */
    public function handle(VirtualUserGroupChanged $event): void
    {
        if ($event->added) {
            // Группа добавлена - выдаем права с флагом auto_grant
            try {
                $this->autoGrantAction->handle($event->userId, $event->groupId);
            } catch (\Exception $e) {
                Log::error('Failed to auto-grant permissions for group', [
                    'user_id' => $event->userId,
                    'group_id' => $event->groupId,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            // Группа удалена - отзываем права с флагом auto_revoke
            try {
                $this->autoRevokeAction->handle($event->userId, $event->groupId);
            } catch (\Exception $e) {
                Log::error('Failed to auto-revoke permissions for group', [
                    'user_id' => $event->userId,
                    'group_id' => $event->groupId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
