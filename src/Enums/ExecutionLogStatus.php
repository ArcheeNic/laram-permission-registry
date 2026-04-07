<?php

namespace ArcheeNic\PermissionRegistry\Enums;

enum ExecutionLogStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case SUCCESS = 'success';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Ожидает',
            self::RUNNING => 'Выполняется',
            self::SUCCESS => 'Успешно',
            self::FAILED => 'Ошибка',
        };
    }
}
