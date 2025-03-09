<?php

namespace App\Modules\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    public const ID = 'id';
    public const NAME = 'name';
    public const DESCRIPTION = 'description';
    public const PARENT_ID = 'parent_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::NAME,
        self::DESCRIPTION,
        self::PARENT_ID,
    ];
}
