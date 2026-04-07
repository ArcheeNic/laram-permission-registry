<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\VirtualUserPosition as BaseVirtualUserPosition;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualUserPosition extends BaseVirtualUserPosition
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(VirtualUser::class, 'virtual_user_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}
