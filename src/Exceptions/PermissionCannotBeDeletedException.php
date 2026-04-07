<?php

namespace ArcheeNic\PermissionRegistry\Exceptions;

use DomainException;
use Illuminate\Support\Collection;

class PermissionCannotBeDeletedException extends DomainException
{
    public static function hasActiveGrants(int $count): self
    {
        return new self(
            __('permission-registry::Permission has active grants', ['count' => $count])
        );
    }

    public static function hasDependents(Collection $dependentNames): self
    {
        return new self(
            __('permission-registry::Permission is required by other permissions', [
                'permissions' => $dependentNames->implode(', '),
            ])
        );
    }

    public function getUserMessage(): string
    {
        return $this->getMessage();
    }
}
