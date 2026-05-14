<?php

namespace ArcheeNic\PermissionRegistry\Support;

use ArcheeNic\PermissionRegistry\Enums\PermissionFieldType;

class FieldMetaOptionRegistry
{
    /** @var array<string, FieldMetaOption> */
    private array $options = [];

    public function register(FieldMetaOption $option): void
    {
        $this->options[$option->key] = $option;
    }

    /**
     * @return array<int, FieldMetaOption>
     */
    public function forFieldType(?PermissionFieldType $type): array
    {
        if ($type === null) {
            return [];
        }

        return array_values(array_filter(
            $this->options,
            static fn (FieldMetaOption $option): bool => $option->appliesTo($type),
        ));
    }
}
