<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\Position as BasePosition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends BasePosition
{
    use HasFactory;

    protected static function newFactory()
    {
        return \ArcheeNic\PermissionRegistry\Database\Factories\PositionFactory::new();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Position::class, 'parent_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'position_permission');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(PermissionGroup::class, 'position_permission_group');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(VirtualUser::class, 'virtual_user_positions', 'position_id', 'virtual_user_id');
    }

    /**
     * Full hierarchy path for display: "Root -> Child -> Leaf". Root alone returns its name only.
     */
    public function hierarchyPathLabel(): string
    {
        $names = collect();
        $current = $this;

        while ($current !== null) {
            $names->prepend($current->name);

            if ($current->parent_id === null) {
                break;
            }

            if (! $current->relationLoaded('parent')) {
                $current->loadMissing('parent');
            }

            $current = $current->parent;
        }

        return $names->implode(' -> ');
    }
}
