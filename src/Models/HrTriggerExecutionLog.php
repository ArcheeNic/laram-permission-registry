<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\HrTriggerExecutionLog as BaseHrTriggerExecutionLog;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrTriggerExecutionLog extends BaseHrTriggerExecutionLog
{
    protected $casts = [
        self::STARTED_AT => 'datetime',
        self::COMPLETED_AT => 'datetime',
        self::META => 'array',
        self::RESOLUTION_CONTEXT => 'array',
    ];

    public function virtualUser(): BelongsTo
    {
        return $this->belongsTo(VirtualUser::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(HrEventTriggerAssignment::class, self::HR_EVENT_TRIGGER_ASSIGNMENT_ID);
    }

    public function trigger(): BelongsTo
    {
        return $this->belongsTo(PermissionTrigger::class, self::PERMISSION_TRIGGER_ID);
    }
}
