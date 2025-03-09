<?php

namespace ArcheeNic\PermissionRegistry\Models;

use ArcheeNic\PermissionRegistry\Models\Base\UserPosition as BaseUserPosition;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPosition extends BaseUserPosition
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(VirtualUser::class, 'user_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}
