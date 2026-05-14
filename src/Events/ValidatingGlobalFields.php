<?php

namespace ArcheeNic\PermissionRegistry\Events;

class ValidatingGlobalFields
{
    /**
     * @param  array<int, string|null>  $fields
     * @param  array<int, array<string, mixed>>  $fieldsMeta
     */
    public function __construct(
        public readonly int $virtualUserId,
        public readonly array $fields,
        public readonly array $fieldsMeta,
    ) {}
}
