<?php

namespace App\Modules\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class PermissionField extends Model
{
    public const ID = 'id';
    public const NAME = 'name';
    public const DEFAULT_VALUE = 'default_value';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::NAME,
        self::DEFAULT_VALUE,
    ];
}
