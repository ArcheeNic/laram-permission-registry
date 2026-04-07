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

        $log->update([
            ImportExecutionLog::STATUS => ImportExecutionStatus::RUNNING->value,
        ]);

        $emailFieldId = $this->resolveEmailFieldId($permissionImportId);
        $processedEmails = [];

        foreach ($result->users as $userData) {
            $email = $this->extractEmail($userData, $fieldMappingSchema, $emailFieldId);
            $matchResult = $this->matchVirtualUser($email, $emailFieldId, $userData, $fieldMappingSchema);

            if ($email !== null) {
                $processedEmails[] = mb_strtolower($email);
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

        $this->createMissingStagingRows($importRunId, $permissionImportId, $emailFieldId, $processedEmails);

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

        $fieldValue = VirtualUserFieldValue::query()
            ->where(VirtualUserFieldValue::PERMISSION_FIELD_ID, $emailFieldId)
            ->where(VirtualUserFieldValue::VALUE, $email)
            ->whereHas('virtualUser', fn ($q) => $q->where('status', '!=', VirtualUserStatus::DEACTIVATED->value))
            ->with('virtualUser')
            ->first();

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
     */
    private function createMissingStagingRows(
        string $importRunId,
        int $permissionImportId,
        ?int $emailFieldId,
        array $processedEmails,
    ): void {
        if ($emailFieldId === null) {
            return;
        }

        $query = VirtualUserFieldValue::query()
            ->where(VirtualUserFieldValue::PERMISSION_FIELD_ID, $emailFieldId)
            ->whereHas('virtualUser', fn ($q) => $q->where('status', '!=', VirtualUserStatus::DEACTIVATED->value));

        if (!empty($processedEmails)) {
            $query->whereNotIn(VirtualUserFieldValue::VALUE, $processedEmails);
        }

        $query->each(function (VirtualUserFieldValue $fieldValue) use ($importRunId, $permissionImportId) {
            ImportStagingRow::query()->create([
                ImportStagingRow::IMPORT_RUN_ID => $importRunId,
                ImportStagingRow::PERMISSION_IMPORT_ID => $permissionImportId,
                ImportStagingRow::EXTERNAL_ID => 'missing_' . $fieldValue->{VirtualUserFieldValue::VIRTUAL_USER_ID},
                ImportStagingRow::FIELDS => [],
                ImportStagingRow::MATCH_STATUS => ImportMatchStatus::MISSING->value,
                ImportStagingRow::MATCHED_VIRTUAL_USER_ID => $fieldValue->{VirtualUserFieldValue::VIRTUAL_USER_ID},
            ]);
        });
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
}
