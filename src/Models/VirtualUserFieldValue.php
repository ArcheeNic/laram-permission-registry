<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\VirtualUserFieldValue as BaseVirtualUserFieldValue;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Global field values for virtual users
 *
 * Stores values for fields marked as is_global=true.
 * These values are shared across all permissions for a user.
 *
 * @property int $id
 * @property int $virtual_user_id
 * @property int $permission_field_id
 * @property string|null $value
 * @property string $source
 * @property int|null $created_by
 * @property array<string, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class VirtualUserFieldValue extends BaseVirtualUserFieldValue
{
    protected $casts = [
        self::META => 'array',
    ];

    public function virtualUser(): BelongsTo
    {
        return $this->belongsTo(VirtualUser::class, 'virtual_user_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(PermissionField::class, 'permission_field_id');
    }

    public function getMeta(string $key, mixed $default = null): mixed
    {
        $meta = $this->{self::META};

        if (! is_array($meta) || ! array_key_exists($key, $meta)) {
            return $default;
        }

        return $meta[$key];
    }

    public function setMeta(string $key, mixed $value): self
    {
        $meta = $this->{self::META};
        $meta = is_array($meta) ? $meta : [];
        $meta[$key] = $value;

        $this->{self::META} = $meta;

        return $this;
    }

    public function forgetMeta(string $key): self
    {
        $meta = $this->{self::META};

        if (! is_array($meta) || ! array_key_exists($key, $meta)) {
            return $this;
        }

        unset($meta[$key]);
        $this->{self::META} = $meta === [] ? null : $meta;

        return $this;
    }
}
