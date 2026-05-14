<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\ImportExecutionStatus;
use ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus;
use ArcheeNic\PermissionRegistry\Enums\PermissionFieldType;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\ImportExecutionLog;
use ArcheeNic\PermissionRegistry\Models\ImportFieldMapping;
use ArcheeNic\PermissionRegistry\Models\ImportStagingRow;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\PermissionImport;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use ArcheeNic\PermissionRegistry\Services\ImportDiscoveryService;
use ArcheeNic\PermissionRegistry\Services\ImportFieldMappingService;
use ArcheeNic\PermissionRegistry\ValueObjects\ImportContext;
use Illuminate\Support\Str;

class FetchImportAction
{
    public function __construct(
        private ImportFieldMappingService $fieldMappingService,
        private ImportDiscoveryService $discoveryService,
        private RecalculateImportStatusesAction $recalculateStatuses,
    ) {}

    public function handle(int $permissionImportId): string
    {
        $import = PermissionImport::query()->findOrFail($permissionImportId);

        $this->validateImporterClass($import->{PermissionImport::CLASS_NAME});

        $importRunId = Str::uuid()->toString();

        $log = ImportExecutionLog::query()->create([
            ImportExecutionLog::IMPORT_RUN_ID => $importRunId,
            ImportExecutionLog::PERMISSION_IMPORT_ID => $permissionImportId,
            ImportExecutionLog::STATUS => ImportExecutionStatus::PENDING->value,
            ImportExecutionLog::STARTED_AT => now(),
        ]);

        $importer = app($import->{PermissionImport::CLASS_NAME});
        $fieldMappingSchema = $this->fieldMappingService->getFieldMappingSchema($permissionImportId);

        $context = new ImportContext(
            permissionImportId: $permissionImportId,
            config: [],
            fieldMappingSchema: $fieldMappingSchema,
        );

        $result = $importer->execute($context);

        if ($result->hasErrors() && $result->userCount() === 0) {
            $log->update([
                ImportExecutionLog::STATUS => ImportExecutionStatus::FAILED->value,
                ImportExecutionLog::COMPLETED_AT => now(),
                ImportExecutionLog::ERROR_MESSAGE => implode('; ', $result->errors),
            ]);

            throw new \RuntimeException(implode('; ', $result->errors));
        }

        $log->update([
            ImportExecutionLog::STATUS => ImportExecutionStatus::RUNNING->value,
        ]);

        $mappedEmailFieldIds = $this->resolveMappedEmailFieldIds($permissionImportId, $fieldMappingSchema);
        $allEmailFieldIds = $this->resolveAllEmailFieldIds();
        $matchedVirtualUserIds = [];

        foreach ($result->users as $userData) {
            $emails = $this->extractEmails($userData, $fieldMappingSchema, $mappedEmailFieldIds);
            $matchResult = $this->matchByEmails($emails, $allEmailFieldIds, $userData, $fieldMappingSchema, $mappedEmailFieldIds);

            if ($matchResult['virtual_user_id'] !== null) {
                $matchedVirtualUserIds[] = $matchResult['virtual_user_id'];
            }

            ImportStagingRow::query()->create([
                ImportStagingRow::IMPORT_RUN_ID => $importRunId,
                ImportStagingRow::PERMISSION_IMPORT_ID => $permissionImportId,
                ImportStagingRow::EXTERNAL_ID => $userData['external_id'] ?? ($emails[0] ?? Str::uuid()->toString()),
                ImportStagingRow::FIELDS => $userData,
                ImportStagingRow::MATCH_STATUS => $matchResult['status']->value,
                ImportStagingRow::MATCHED_VIRTUAL_USER_ID => $matchResult['virtual_user_id'],
            ]);
        }

        $this->createMissingStagingRows(
            $importRunId,
            $permissionImportId,
            $allEmailFieldIds,
            array_values(array_unique($matchedVirtualUserIds)),
            $fieldMappingSchema,
        );

        $this->recalculateStatuses->handle($importRunId, $permissionImportId);

        $stats = $this->buildStats($importRunId);
        $log->update([
            ImportExecutionLog::STATUS => ImportExecutionStatus::COMPLETED->value,
            ImportExecutionLog::COMPLETED_AT => now(),
            ImportExecutionLog::STATS => $stats,
        ]);

        return $importRunId;
    }

    private function validateImporterClass(string $className): void
    {
        $metadata = $this->discoveryService->getImportMetadata($className);

        if ($metadata === null) {
            throw new \RuntimeException("Importer class is not valid: {$className}");
        }
    }

    /**
     * @param array<string, array{permission_field_id: int, is_internal: bool}> $fieldMappingSchema
     * @return array<int, int>
     */
    private function resolveMappedEmailFieldIds(int $permissionImportId, array $fieldMappingSchema): array
    {
        $mappedFieldIds = array_values(array_unique(array_map(
            static fn (array $mapping): int => $mapping['permission_field_id'],
            $fieldMappingSchema,
        )));

        if ($mappedFieldIds === []) {
            return [];
        }

        return PermissionField::query()
            ->whereIn(PermissionField::ID, $mappedFieldIds)
            ->ofType(PermissionFieldType::EMAIL)
            ->pluck(PermissionField::ID)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function resolveAllEmailFieldIds(): array
    {
        return PermissionField::query()
            ->ofType(PermissionFieldType::EMAIL)
            ->pluck(PermissionField::ID)
            ->all();
    }

    /**
     * @param array<string, mixed> $userData
     * @param array<string, array{permission_field_id: int, is_internal: bool}> $fieldMappingSchema
     * @param array<int, int> $emailFieldIds
     * @return array<int, string>
     */
    private function extractEmails(array $userData, array $fieldMappingSchema, array $emailFieldIds): array
    {
        if ($emailFieldIds === []) {
            return [];
        }

        $emails = [];
        foreach ($fieldMappingSchema as $importFieldName => $mappingData) {
            if (! in_array($mappingData['permission_field_id'], $emailFieldIds, true)) {
                continue;
            }

            $raw = $userData[$importFieldName] ?? null;
            if (! is_string($raw)) {
                continue;
            }

            $normalized = PermissionFieldType::EMAIL->normalize($raw);
            if ($normalized !== null) {
                $emails[$normalized] = $normalized;
            }
        }

        return array_values($emails);
    }

    /**
     * @param array<int, string> $emails
     * @param array<int, int> $allEmailFieldIds
     * @param array<string, mixed> $userData
     * @param array<string, array{permission_field_id: int, is_internal: bool}> $fieldMappingSchema
     * @param array<int, int> $mappedEmailFieldIds
     * @return array{status: ImportMatchStatus, virtual_user_id: int|null}
     */
    private function matchByEmails(
        array $emails,
        array $allEmailFieldIds,
        array $userData,
        array $fieldMappingSchema,
        array $mappedEmailFieldIds,
    ): array {
        if ($emails === [] || $allEmailFieldIds === []) {
            return ['status' => ImportMatchStatus::NEW, 'virtual_user_id' => null];
        }

        $query = VirtualUserFieldValue::query()
            ->whereIn(VirtualUserFieldValue::PERMISSION_FIELD_ID, $allEmailFieldIds)
            ->with('virtualUser');
        $this->applyLowerWhereIn($query, VirtualUserFieldValue::VALUE, $emails);
        $fieldValue = $query->first();

        if ($fieldValue === null) {
            return ['status' => ImportMatchStatus::NEW, 'virtual_user_id' => null];
        }

        $virtualUserId = $fieldValue->{VirtualUserFieldValue::VIRTUAL_USER_ID};
        $status = $this->hasFieldChanges($virtualUserId, $userData, $fieldMappingSchema, $mappedEmailFieldIds)
            ? ImportMatchStatus::CHANGED
            : ImportMatchStatus::EXISTS;

        return ['status' => $status, 'virtual_user_id' => $virtualUserId];
    }

    /**
     * @param array<string, mixed> $userData
     * @param array<string, array{permission_field_id: int, is_internal: bool}> $fieldMappingSchema
     * @param array<int, int> $mappedEmailFieldIds
     */
    private function hasFieldChanges(int $virtualUserId, array $userData, array $fieldMappingSchema, array $mappedEmailFieldIds): bool
    {
        $existingValues = VirtualUserFieldValue::query()
            ->where(VirtualUserFieldValue::VIRTUAL_USER_ID, $virtualUserId)
            ->get()
            ->keyBy(VirtualUserFieldValue::PERMISSION_FIELD_ID);

        foreach ($fieldMappingSchema as $importFieldName => $mappingData) {
            $fieldId = $mappingData['permission_field_id'];
            if (in_array($fieldId, $mappedEmailFieldIds, true)) {
                continue;
            }

            $importedValue = $userData[$importFieldName] ?? null;
            $existingField = $existingValues->get($fieldId);
            $existingValue = $existingField?->{VirtualUserFieldValue::VALUE};

            if ((string) $importedValue !== (string) $existingValue) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, int> $allEmailFieldIds
     * @param array<int, int> $matchedVirtualUserIds
     * @param array<string, array{permission_field_id: int, is_internal: bool}> $fieldMappingSchema
     */
    private function createMissingStagingRows(
        string $importRunId,
        int $permissionImportId,
        array $allEmailFieldIds,
        array $matchedVirtualUserIds,
        array $fieldMappingSchema,
    ): void {
        if ($allEmailFieldIds === []) {
            return;
        }

        $reverseMap = $this->buildReverseFieldMap($fieldMappingSchema);

        $query = VirtualUserFieldValue::query()
            ->whereIn(VirtualUserFieldValue::PERMISSION_FIELD_ID, $allEmailFieldIds)
            ->whereHas('virtualUser', fn ($q) => $q->where('status', '!=', VirtualUserStatus::DEACTIVATED->value));

        if ($matchedVirtualUserIds !== []) {
            $query->whereNotIn(VirtualUserFieldValue::VIRTUAL_USER_ID, $matchedVirtualUserIds);
        }

        $missingUserIds = $query
            ->pluck(VirtualUserFieldValue::VIRTUAL_USER_ID)
            ->unique()
            ->values()
            ->all();

        foreach ($missingUserIds as $virtualUserId) {
            $fields = $this->buildFieldsFromExistingUser($virtualUserId, $reverseMap);

            ImportStagingRow::query()->create([
                ImportStagingRow::IMPORT_RUN_ID => $importRunId,
                ImportStagingRow::PERMISSION_IMPORT_ID => $permissionImportId,
                ImportStagingRow::EXTERNAL_ID => 'missing_'.$virtualUserId,
                ImportStagingRow::FIELDS => $fields,
                ImportStagingRow::MATCH_STATUS => ImportMatchStatus::MISSING->value,
                ImportStagingRow::MATCHED_VIRTUAL_USER_ID => $virtualUserId,
            ]);
        }
    }

    /**
     * @param array<string, array{permission_field_id: int, is_internal: bool}> $fieldMappingSchema
     * @return array<int, string> permission_field_id => import_field_name
     */
    private function buildReverseFieldMap(array $fieldMappingSchema): array
    {
        $map = [];
        foreach ($fieldMappingSchema as $importFieldName => $mappingData) {
            $map[$mappingData['permission_field_id']] = $importFieldName;
        }

        return $map;
    }

    /**
     * @param array<int, string> $reverseMap permission_field_id => import_field_name
     * @return array<string, string> import_field_name => value
     */
    private function buildFieldsFromExistingUser(int $virtualUserId, array $reverseMap): array
    {
        $fieldValues = VirtualUserFieldValue::query()
            ->where(VirtualUserFieldValue::VIRTUAL_USER_ID, $virtualUserId)
            ->whereIn(VirtualUserFieldValue::PERMISSION_FIELD_ID, array_keys($reverseMap))
            ->get();

        $fields = [];
        foreach ($fieldValues as $fv) {
            $fieldId = $fv->{VirtualUserFieldValue::PERMISSION_FIELD_ID};
            if (isset($reverseMap[$fieldId])) {
                $fields[$reverseMap[$fieldId]] = $fv->{VirtualUserFieldValue::VALUE};
            }
        }

        return $fields;
    }

    private function buildStats(string $importRunId): array
    {
        $rows = ImportStagingRow::query()
            ->where(ImportStagingRow::IMPORT_RUN_ID, $importRunId)
            ->get();

        return [
            'total' => $rows->count(),
            'new' => $rows->where(ImportStagingRow::MATCH_STATUS, ImportMatchStatus::NEW)->count(),
            'exists' => $rows->where(ImportStagingRow::MATCH_STATUS, ImportMatchStatus::EXISTS)->count(),
            'changed' => $rows->where(ImportStagingRow::MATCH_STATUS, ImportMatchStatus::CHANGED)->count(),
            'missing' => $rows->where(ImportStagingRow::MATCH_STATUS, ImportMatchStatus::MISSING)->count(),
        ];
    }

    /**
     * @param array<int, string> $values
     */
    private function applyLowerWhereIn($query, string $column, array $values): void
    {
        if ($values === []) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $query->whereRaw("LOWER({$column}) IN ({$placeholders})", array_values($values));
    }
}
