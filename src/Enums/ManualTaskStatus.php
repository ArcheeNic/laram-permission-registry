<?php

namespace ArcheeNic\PermissionRegistry\Enums;

enum ManualTaskStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('permission-registry::governance.manual_task_status.pending'),
            self::IN_PROGRESS => __('permission-registry::governance.manual_task_status.in_progress'),
            self::COMPLETED => __('permission-registry::governance.manual_task_status.completed'),
            self::CANCELLED => __('permission-registry::governance.manual_task_status.cancelled'),
            self::EXPIRED => __('permission-registry::governance.manual_task_status.expired'),
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED, self::EXPIRED]);
    }
}
