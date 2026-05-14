<?php

namespace ArcheeNic\PermissionRegistry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PositionPermissionResource extends Model
{
    public const ID = 'id';

    public const POSITION_ID = 'position_id';

    public const PERMISSION_ID = 'permission_id';

    public const RESOURCE_ID = 'resource_id';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    protected $table = 'position_permission_resources';

    protected $fillable = [
        self::POSITION_ID,
        self::PERMISSION_ID,
        self::RESOURCE_ID,
    ];

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, self::POSITION_ID);
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
