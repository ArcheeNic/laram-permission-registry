<?php

namespace ArcheeNic\PermissionRegistry\Livewire\Concerns;

trait HasFlashMessages
{
    public ?string $flashMessage = null;
    public ?string $flashError = null;
    public ?string $flashWarning = null;

    protected function setFlashMessage(string $message): void
    {
        $this->flashMessage = $message;
        $this->flashError = null;
        $this->flashWarning = null;
    }

    protected function setFlashError(string $error): void
    {
        $this->flashError = $error;
        $this->flashMessage = null;
        $this->flashWarning = null;
    }

    protected function setFlashWarning(string $warning): void
    {
        $this->flashWarning = $warning;
        $this->flashMessage = null;
        $this->flashError = null;
    }

    protected function clearFlashMessages(): void
    {
        $this->flashMessage = null;
        $this->flashError = null;
        $this->flashWarning = null;
    }
}
