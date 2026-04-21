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

    /**
     * Class patterns of triggers that define permission matching for this import.
     *
     * Example: ['App\\Triggers\\Bitrix24%']
     *
     * @return array<int, string>
     */
    public function getRelatedTriggerClassPatterns(): array;

    /**
     * External field name containing source department IDs.
     */
    public function getDepartmentFieldName(): string;

    /**
     * Class of the fallback grant trigger used when no departments matched.
     *
     * Return null if the import has no fallback.
     */
    public function getFallbackTriggerClass(): ?string;
}
