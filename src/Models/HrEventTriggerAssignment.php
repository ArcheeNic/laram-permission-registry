<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use ArcheeNic\PermissionRegistry\Models\Base\HrEventTriggerAssignment as BaseHrEventTriggerAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrEventTriggerAssignment extends BaseHrEventTriggerAssignment
{
    protected $casts = [
        self::IS_ENABLED => 'boolean',
        self::CONFIG => 'array',
        self::ORDER => 'integer',
        self::EMPLOYEE_CATEGORY => EmployeeCategory::class,
    ];

    public function trigger(): BelongsTo
    {
        return $this->belongsTo(PermissionTrigger::class, 'permission_trigger_id');
    }

    public function scopeForCategory(Builder $query, EmployeeCategory $category): Builder
    {
        return $query->where(self::EMPLOYEE_CATEGORY, $category->value);
    }
}
