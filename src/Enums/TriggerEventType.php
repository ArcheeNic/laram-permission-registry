<?php

namespace ArcheeNic\PermissionRegistry\Enums;

enum TriggerEventType: string
{
    case GRANT = 'grant';
    case REVOKE = 'revoke';

    public function label(): string
    {
        return match ($this) {
            self::GRANT => 'Выдача',
            self::REVOKE => 'Отзыв',
        };
    }
}
