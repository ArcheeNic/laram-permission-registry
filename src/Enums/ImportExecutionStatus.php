<?php

namespace ArcheeNic\PermissionRegistry\Enums;

enum ImportExecutionStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
