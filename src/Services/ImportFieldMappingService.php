<?php

namespace ArcheeNic\PermissionRegistry\Services;

use ArcheeNic\PermissionRegistry\Models\ImportFieldMapping;
use Illuminate\Support\Facades\Cache;

class ImportFieldMappingService
{
    /**
     * @param int $permissionImportId
     * @return array<string, array{permission_field_id: int, is_internal: bool}>
     */
    public function getMapping(int $permissionImportId): array
    {
        $cacheKey = "import_field_mapping_{$permissionImportId}";

        return Cache::remember($cacheKey, 3600, function () use ($permissionImportId) {
            return ImportFieldMapping::with('permissionField')
                ->where(ImportFieldMapping::PERMISSION_IMPORT_ID, $permissionImportId)
                ->get()
                ->mapWithKeys(fn (ImportFieldMapping $mapping) => [
                    $mapping->{ImportFieldMapping::IMPORT_FIELD_NAME} => [
                        'permission_field_id' => $mapping->{ImportFieldMapping::PERMISSION_FIELD_ID},
                        'is_internal' => (bool) $mapping->{ImportFieldMapping::IS_INTERNAL},
                    ],
                ])
                ->toArray();
        });
    }

    public function clearCache(int $permissionImportId): void
    {
        Cache::forget("import_field_mapping_{$permissionImportId}");
    }

    /**
     * @param array<string, mixed> $externalFields
     * @param array<string, array{permission_field_id: int, is_internal: bool}> $mapping
     * @return array<int, mixed> permission_field_id => value
     */
    public function applyMapping(array $externalFields, array $mapping): array
    {
        $result = [];

        foreach ($mapping as $importFieldName => $mappingData) {
            if (! array_key_exists($importFieldName, $externalFields)) {
                continue;
            }

            $result[$mappingData['permission_field_id']] = $externalFields[$importFieldName];
        }

        return $result;
    }

    /**
     * @return array<string, array{permission_field_id: int, is_internal: bool}>
     */
    public function getFieldMappingSchema(int $permissionImportId): array
    {
        return $this->getMapping($permissionImportId);
    }
}
