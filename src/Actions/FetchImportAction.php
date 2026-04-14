<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\ImportExecutionStatus;
use ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\ImportExecutionLog;
use ArcheeNic\PermissionRegistry\Models\ImportFieldMapping;
use ArcheeNic\PermissionRegistry\Models\ImportStagingRow;
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

        $emailFieldId = $this->resolveEmailFieldId($permissionImportId);
        $processedEmails = [];

        foreach ($result->users as $userData) {
            $email = $this->extractEmail($userData, $fieldMappingSchema, $emailFieldId);
            $matchResult = $this->matchVirtualUser($email, $emailFieldId, $userData, $fieldMappingSchema);

            if ($email !== null) {
                $processedEmails[] = $this->normalizeEmail($email);
            }

            ImportStagingRow::query()->create([
                ImportStagingRow::IMPORT_RUN_ID => $importRunId,
                ImportStagingRow::PERMISSION_IMPORT_ID => $permissionImportId,
                ImportStagingRow::EXTERNAL_ID => $userData['external_id'] ?? $email ?? Str::uuid()->toString(),
                ImportStagingRow::FIELDS => $userData,
                ImportStagingRow::MATCH_STATUS => $matchResult['status']->value,
                ImportStagingRow::MATCHED_VIRTUAL_USER_ID => $matchResult['virtual_user_id'],
            ]);
        }

        $this->createMissingStagingRows($importRunId, $permissionImportId, $emailFieldId, $processedEmails, $fieldMappingSchema);

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

    private function resolveEmailFieldId(int $permissionImportId): ?int
    {
        $mapping = ImportFieldMapping::query()
            ->where(ImportFieldMapping::PERMISSION_IMPORT_ID, $permissionImportId)
            ->where(ImportFieldMapping::IS_INTERNAL, true)
            ->first();

        return $mapping?->{ImportFieldMapping::PERMISSION_FIELD_ID};
    }

    /**
     * @param array<string, mixed> $userData
     * @param array<string, array{permission_field_id: int, is_internal: bool}> $fieldMappingSchema
     */
    private function extractEmail(array $userData, array $fieldMappingSchema, ?int $emailFieldId): ?string
    {
        if ($emailFieldId === null) {
            return null;
        }

        foreach ($fieldMappingSchema as $importFieldName => $mappingData) {
            if ($mappingData['permission_field_id'] === $emailFieldId && isset($userData[$importFieldName])) {
                return $userData[$importFieldName];
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $userData
     * @param array<string, array{permission_field_id: int, is_internal: bool}> $fieldMappingSchema
     * @return array{status: ImportMatchStatus, virtual_user_id: int|null}
     */
    private function matchVirtualUser(?string $email, ?int $emailFieldId, array $userData, array $fieldMappingSchema): array
    {
        if ($email === null || $emailFieldId === null) {
            return ['status' => ImportMatchStatus::NEW, 'virtual_user_id' => null];
        }

        $normalizedEmail = $this->normalizeEmail($email);

        $fieldValueQuery = VirtualUserFieldValue::query()
            ->where(VirtualUserFieldValue::PERMISSION_FIELD_ID, $emailFieldId)
            ->whereHas('virtualUser', fn ($q) => $q->where('status', '!=', VirtualUserStatus::DEACTIVATED->value))
            ->with('virtualUser');
        $this->applyCaseInsensitiveEmailEquals($fieldValueQuery, VirtualUserFieldValue::VALUE, $normalizedEmail);
        $fieldValue = $fieldValueQuery->first();

        if ($fieldValue === null) {
            return ['status' => ImportMatchStatus::NEW, 'virtual_user_id' => null];
        }

        $virtualUserId = $fieldValue->{VirtualUserFieldValue::VIRTUAL_USER_ID};
        $status = $this->hasFieldChanges($virtualUserId, $userData, $fieldMappingSchema, $emailFieldId)
            ? ImportMatchStatus::CHANGED
            : ImportMatchStatus::EXISTS;

        return ['status' => $status, 'virtual_user_id' => $virtualUserId];
    }

    /**
     * @param array<string, mixed> $userData
     * @param array<string, array{permission_field_id: int, is_internal: bool}> $fieldMappingSchema
     */
    private function hasFieldChanges(int $virtualUserId, array $userData, array $fieldMappingSchema, int $emailFieldId): bool
    {
        $existingValues = VirtualUserFieldValue::query()
            ->where(VirtualUserFieldValue::VIRTUAL_USER_ID, $virtualUserId)
            ->get()
            ->keyBy(VirtualUserFieldValue::PERMISSION_FIELD_ID);

        foreach ($fieldMappingSchema as $importFieldName => $mappingData) {
            $fieldId = $mappingData['permission_field_id'];
            if ($fieldId === $emailFieldId) {
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
     * @param string[] $processedEmails
     * @param array<string, array{permission_field_id: int, is_internal: bool}> $fieldMappingSchema
     */
    private function createMissingStagingRows(
        string $importRunId,
        int $permissionImportId,
        ?int $emailFieldId,
        array $processedEmails,
        array $fieldMappingSchema,
    ): void {
        if ($emailFieldId === null) {
            return;
        }

        $reverseMap = $this->buildReverseFieldMap($fieldMappingSchema);

        $query = VirtualUserFieldValue::query()
            ->where(VirtualUserFieldValue::PERMISSION_FIELD_ID, $emailFieldId)
            ->whereHas('virtualUser', fn ($q) => $q->where('status', '!=', VirtualUserStatus::DEACTIVATED->value));

        if (!empty($processedEmails)) {
            $this->applyCaseInsensitiveEmailNotIn($query, VirtualUserFieldValue::VALUE, $processedEmails);
        }

        $query->each(function (VirtualUserFieldValue $fieldValue) use ($importRunId, $permissionImportId, $reverseMap) {
            $virtualUserId = $fieldValue->{VirtualUserFieldValue::VIRTUAL_USER_ID};
            $fields = $this->buildFieldsFromExistingUser($virtualUserId, $reverseMap);

            ImportStagingRow::query()->create([
                ImportStagingRow::IMPORT_RUN_ID => $importRunId,
                ImportStagingRow::PERMISSION_IMPORT_ID => $permissionImportId,
                ImportStagingRow::EXTERNAL_ID => 'missing_' . $virtualUserId,
                ImportStagingRow::FIELDS => $fields,
                ImportStagingRow::MATCH_STATUS => ImportMatchStatus::MISSING->value,
                ImportStagingRow::MATCHED_VIRTUAL_USER_ID => $virtualUserId,
            ]);
        });
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

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function applyCaseInsensitiveEmailEquals($query, string $column, string $email): void
    {
        $query->whereRaw("LOWER({$column}) = ?", [$email]);
    }

    /**
     * @param array<int, string> $emails
     */
    private function applyCaseInsensitiveEmailNotIn($query, string $column, array $emails): void
    {
        $normalizedEmails = array_values(array_unique(array_filter(
            array_map(fn (string $email): string => $this->normalizeEmail($email), $emails),
            fn (string $email): bool => $email !== ''
        )));

        if ($normalizedEmails === []) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($normalizedEmails), '?'));
        $query->whereRaw("LOWER({$column}) NOT IN ({$placeholders})", $normalizedEmails);
    }
}
