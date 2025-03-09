<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    public const ID = 'id';
    public const SERVICE = 'service';
    public const NAME = 'name';
    public const DESCRIPTION = 'description';
    public const TAGS = 'tags';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::SERVICE,
        self::NAME,
        self::DESCRIPTION,
        self::TAGS,
    ];
}
