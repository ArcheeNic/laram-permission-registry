<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\PermissionResource as BasePermissionResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PermissionResource extends BasePermissionResource
{
    use HasFactory;
    use SoftDeletes;

    public function grantedPermissions(): HasMany
    {
        return $this->hasMany(GrantedPermission::class, 'resource_id');
    }

    public static function findByExternal(string $service, string $kind, string $externalId): ?self
    {
        return static::query()
            ->where(self::SERVICE, $service)
            ->where(self::KIND, $kind)
            ->where(self::EXTERNAL_ID, $externalId)
            ->first();
    }
}
