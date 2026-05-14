<?php

namespace ArcheeNic\PermissionRegistry\Support;

use ArcheeNic\PermissionRegistry\Enums\PermissionFieldType;

final class FieldMetaOption
{
    /**
     * @param  array<int, PermissionFieldType>  $applicableFieldTypes
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly bool $defaultValue,
        public readonly array $applicableFieldTypes,
    ) {}

    public function appliesTo(PermissionFieldType $type): bool
    {
        return in_array($type, $this->applicableFieldTypes, true);
    }
}
