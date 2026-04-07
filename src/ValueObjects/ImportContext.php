<?php

namespace ArcheeNic\PermissionRegistry\ValueObjects;

readonly class ImportContext
{
    public function __construct(
        public int $permissionImportId,
        public array $config,
        public array $fieldMappingSchema,
    ) {
    }
}
