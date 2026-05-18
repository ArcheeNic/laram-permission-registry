<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property string $service
 * @property string $kind
 * @property string $external_id
 * @property string $name
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $synced_at
 * @property bool $present_in_source
 * @property bool $is_ignored
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class PermissionResource extends Model
{
    public const ID = 'id';

    public const TENANT_ID = 'tenant_id';

    public const SERVICE = 'service';

    public const KIND = 'kind';

    public const EXTERNAL_ID = 'external_id';

    public const NAME = 'name';

    public const METADATA = 'metadata';

    public const SYNCED_AT = 'synced_at';

    public const PRESENT_IN_SOURCE = 'present_in_source';

    public const IS_IGNORED = 'is_ignored';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    public const DELETED_AT = 'deleted_at';

    protected $table = 'permission_resources';

    protected $fillable = [
        self::TENANT_ID,
        self::SERVICE,
        self::KIND,
        self::EXTERNAL_ID,
        self::NAME,
        self::METADATA,
        self::SYNCED_AT,
        self::PRESENT_IN_SOURCE,
        self::IS_IGNORED,
    ];

    protected $casts = [
        self::METADATA => 'array',
        self::PRESENT_IN_SOURCE => 'boolean',
        self::IS_IGNORED => 'boolean',
        self::SYNCED_AT => 'datetime',
    ];
}
