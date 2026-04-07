<?php

namespace ArcheeNic\PermissionRegistry\Contracts;

use ArcheeNic\PermissionRegistry\ValueObjects\ImportContext;
use ArcheeNic\PermissionRegistry\ValueObjects\ImportResult;

interface PermissionImportInterface
{
    public function execute(ImportContext $context): ImportResult;

    public function getName(): string;

    public function getDescription(): string;

    public function getRequiredFields(): array;

    public function getConfigFields(): array;
}
