<?php

namespace ArcheeNic\PermissionRegistry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionGroupResource extends Model
{
    public const ID = 'id';
    public const PERMISSION_GROUP_ID = 'permission_group_id';
    public const PERMISSION_ID = 'permission_id';
    public const RESOURCE_ID = 'resource_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $table = 'permission_group_resources';

    protected $fillable = [
        self::PERMISSION_GROUP_ID,
        self::PERMISSION_ID,
        self::RESOURCE_ID,
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(PermissionGroup::class, self::PERMISSION_GROUP_ID);
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, self::PERMISSION_ID);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(PermissionResource::class, self::RESOURCE_ID);
    }
}
