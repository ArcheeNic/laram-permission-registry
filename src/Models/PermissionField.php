<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Enums\PermissionFieldType;
use ArcheeNic\PermissionRegistry\Models\Base\PermissionField as BasePermissionField;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PermissionField extends BasePermissionField
{
    use HasFactory;

    protected static function newFactory()
    {
        return \ArcheeNic\PermissionRegistry\Database\Factories\PermissionFieldFactory::new();
    }

    protected $casts = [
        self::TYPE => PermissionFieldType::class,
        self::IS_GLOBAL => 'boolean',
        self::REQUIRED_ON_USER_CREATE => 'boolean',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function scopeOfType(Builder $query, PermissionFieldType $type): Builder
    {
        if ($type === PermissionFieldType::EMAIL) {
            return $query->where(function (Builder $q) {
                $q->where(self::TYPE, PermissionFieldType::EMAIL->value)
                    ->orWhere(function (Builder $sub) {
                        $sub->whereIn(self::TYPE, [PermissionFieldType::STRING->value, ''])
                            ->where(function (Builder $name) {
                                $name->where(self::NAME, 'LIKE', '%email%')
                                    ->orWhere(self::NAME, 'LIKE', '%e-mail%')
                                    ->orWhere(self::NAME, 'LIKE', '%почт%');
                            });
                    });
            });
        }

        return $query->where(self::TYPE, $type->value);
    }
}
