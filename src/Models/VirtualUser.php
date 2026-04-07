<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Virtual User Model
 * 
 * IMPORTANT: This module uses 'virtual_users' table instead of 'users' to avoid 
 * conflicts with the application's user table. Always use VirtualUser model 
 * when working with permissions in this module.
 * 
 * Related tables follow the same convention:
 * - virtual_user_field_values: stores global field values for users
 * - virtual_user_positions, virtual_user_groups: pivot tables for many-to-many relations
 */
class VirtualUser extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \ArcheeNic\PermissionRegistry\Database\Factories\VirtualUserFactory::new();
    }

    protected $table = 'virtual_users';

    protected $fillable = [
        'name',
        'user_id',
        'status',
        'employee_category',
        'meta',
    ];

    protected $casts = [
        'status' => VirtualUserStatus::class,
        'employee_category' => EmployeeCategory::class,
        'meta' => 'array',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', VirtualUserStatus::ACTIVE->value);
    }

    public function isActive(): bool
    {
        return $this->status === VirtualUserStatus::ACTIVE;
    }

    /**
     * Позиции пользователя
     */
    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'virtual_user_positions', 'virtual_user_id');
    }

    /**
     * Группы пользователя
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(PermissionGroup::class, 'virtual_user_groups', 'virtual_user_id', 'permission_group_id');
    }

    /**
     * Выданные права пользователя
     */
    public function grantedPermissions(): HasMany
    {
        return $this->hasMany(GrantedPermission::class, 'virtual_user_id');
    }

    /**
     * Глобальные значения полей пользователя
     */
    public function fieldValues(): HasMany
    {
        return $this->hasMany(VirtualUserFieldValue::class, 'virtual_user_id');
    }

    public function hrTriggerExecutionLogs(): HasMany
    {
        return $this->hasMany(HrTriggerExecutionLog::class, 'virtual_user_id');
    }

    public function getEmailForDisplayAttribute(): ?string
    {
        $metaEmail = data_get($this->meta, 'email');
        if (is_string($metaEmail) && filter_var($metaEmail, FILTER_VALIDATE_EMAIL)) {
            return $metaEmail;
        }

        $fieldValues = $this->relationLoaded('fieldValues')
            ? $this->fieldValues
            : $this->fieldValues()->with('field')->get();

        $emailFieldValue = $fieldValues->first(function ($fieldValue) {
            $fieldName = mb_strtolower((string) data_get($fieldValue, 'field.name', ''));
            $value = (string) ($fieldValue->value ?? '');

            if ($value === '' || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return false;
            }

            return str_contains($fieldName, 'email')
                || str_contains($fieldName, 'e-mail')
                || str_contains($fieldName, 'почт');
        });

        return $emailFieldValue?->value;
    }
}
