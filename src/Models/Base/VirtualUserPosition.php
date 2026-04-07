<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class VirtualUserPosition extends Model
{
    protected $table = 'virtual_user_positions';

    public const ID = 'id';
    public const VIRTUAL_USER_ID = 'virtual_user_id';
    public const POSITION_ID = 'position_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::VIRTUAL_USER_ID,
        self::POSITION_ID,
    ];
}
