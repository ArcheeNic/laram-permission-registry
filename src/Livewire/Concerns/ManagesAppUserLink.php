<?php

namespace ArcheeNic\PermissionRegistry\Livewire\Concerns;

use ArcheeNic\PermissionRegistry\Models\VirtualUser;

trait ManagesAppUserLink
{
    /** @var int|null Selected application user id for linking */
    public ?int $linkedAppUserId = null;

    public function getAppUsersForLinkProperty(): array
    {
        if (! app()->bound('permission-registry.app_users_for_link')) {
            return [];
        }

        $users = app('permission-registry.app_users_for_link');
        if (is_callable($users)) {
            $users = $users();
        }

        return collect($users)->map(fn ($u) => [
            'id' => is_array($u) ? ($u['id'] ?? $u['id']) : $u->id,
            'name' => is_array($u) ? ($u['name'] ?? $u['email'] ?? (string) $u['id']) : ($u->name ?? $u->email ?? (string) $u->id),
        ])->values()->all();
    }

    public function saveLinkedAppUser(): void
    {
        $this->clearFlashMessages();

        if (! $this->selectedUserId) {
            return;
        }

        $virtualUser = VirtualUser::find($this->selectedUserId);
        if (! $virtualUser) {
            return;
        }

        $virtualUser->update(['user_id' => $this->linkedAppUserId ?: null]);

        $this->selectUser($this->selectedUserId);
        $this->setFlashMessage(__('permission-registry::Link to app user saved'));
    }
}
