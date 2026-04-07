<?php

namespace ArcheeNic\PermissionRegistry\Exceptions;

use DomainException;

class UserDeactivatedException extends DomainException
{
    public static function cannotGrantPermission(int $userId): self
    {
        $exception = new self(
            __('permission-registry::Cannot grant permissions to deactivated user')
        );
        $exception->userId = $userId;

        return $exception;
    }

    public int $userId = 0;
}
