<?php

namespace ArcheeNic\PermissionRegistry\Services;

use ArcheeNic\PermissionRegistry\Models\PermissionImport;

class ImportTriggerConfigResolver
{
    /**
     * @return array{0: array<int, string>, 1: string, 2: ?string}
     */
    public function resolve(?PermissionImport $import): array
    {
        $defaultPatterns = ['App\\Triggers\\Bitrix24%'];
        $defaultDepartmentField = 'department_ids';
        $defaultFallback = null;

        if (!$import || !class_exists($import->{PermissionImport::CLASS_NAME})) {
            return [$defaultPatterns, $defaultDepartmentField, $defaultFallback];
        }

        try {
            /** @var object $importer */
            $importer = app($import->{PermissionImport::CLASS_NAME});
            if (!method_exists($importer, 'getRelatedTriggerClassPatterns')
                || !method_exists($importer, 'getDepartmentFieldName')) {
                return [$defaultPatterns, $defaultDepartmentField, $defaultFallback];
            }

            $patterns = $this->sanitizePatterns($importer->getRelatedTriggerClassPatterns(), $defaultPatterns);
            $departmentFieldName = $this->sanitizeDepartmentFieldName(
                $importer->getDepartmentFieldName(),
                $defaultDepartmentField
            );
            $fallbackTriggerClass = method_exists($importer, 'getFallbackTriggerClass')
                ? $this->sanitizeFallbackTriggerClass($importer->getFallbackTriggerClass())
                : $defaultFallback;

            return [$patterns, $departmentFieldName, $fallbackTriggerClass];
        } catch (\Throwable) {
            return [$defaultPatterns, $defaultDepartmentField, $defaultFallback];
        }
    }

    private function sanitizeFallbackTriggerClass(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);
        if ($normalized === '' || !str_starts_with($normalized, 'App\\Triggers\\')) {
            return null;
        }

        return $normalized;
    }

    /**
     * @param mixed $rawPatterns
     * @param array<int, string> $defaultPatterns
     * @return array<int, string>
     */
    private function sanitizePatterns(mixed $rawPatterns, array $defaultPatterns): array
    {
        if (!is_array($rawPatterns)) {
            return $defaultPatterns;
        }

        $allowed = [];
        foreach ($rawPatterns as $pattern) {
            if (!is_string($pattern)) {
                continue;
            }

            $normalized = trim($pattern);
            if ($normalized === '' || $normalized === '%') {
                continue;
            }

            // Restrict to application trigger classes to avoid too-wide matching patterns.
            if (!str_starts_with($normalized, 'App\\Triggers\\')) {
                continue;
            }

            $allowed[] = $normalized;
        }

        return $allowed !== [] ? array_values(array_unique($allowed)) : $defaultPatterns;
    }

    private function sanitizeDepartmentFieldName(mixed $fieldName, string $default): string
    {
        if (!is_string($fieldName)) {
            return $default;
        }

        $normalized = trim($fieldName);
        if ($normalized === '') {
            return $default;
        }

        if (!preg_match('/^[a-z0-9_]+$/i', $normalized)) {
            return $default;
        }

        return $normalized;
    }
}
