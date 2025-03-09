<?php

namespace App\Modules\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class UserPosition extends Model
{
    public const ID = 'id';
    public const USER_ID = 'user_id';
    public const POSITION_ID = 'position_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::USER_ID,
        self::POSITION_ID,
    ];
}
