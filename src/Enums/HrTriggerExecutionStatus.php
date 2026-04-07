<?php

namespace ArcheeNic\PermissionRegistry\Enums;

enum HrTriggerExecutionStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case AWAITING_RESOLUTION = 'awaiting_resolution';
}
