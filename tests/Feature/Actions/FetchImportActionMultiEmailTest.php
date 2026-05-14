<?php

namespace ArcheeNic\PermissionRegistry\Tests\Feature\Actions;

use ArcheeNic\PermissionRegistry\Actions\FetchImportAction;
use ArcheeNic\PermissionRegistry\Contracts\PermissionImportInterface;
use ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus;
use ArcheeNic\PermissionRegistry\Enums\PermissionFieldType;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\ImportFieldMapping;
use ArcheeNic\PermissionRegistry\Models\ImportStagingRow;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\PermissionImport;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use ArcheeNic\PermissionRegistry\ValueObjects\ImportContext;
use ArcheeNic\PermissionRegistry\ValueObjects\ImportResult;

class FetchImportActionMultiEmailTest extends TestCase
{
    private PermissionField $corpEmailField;

    private PermissionField $personalEmailField;

    private PermissionImport $import;

    protected function setUp(): void
    {
        parent::setUp();

        $this->corpEmailField = PermissionField::create([
            'name' => 'corp_email',
            'type' => PermissionFieldType::EMAIL->value,
            'is_global' => true,
        ]);
        $this->personalEmailField = PermissionField::create([
            'name' => 'personal_email',
            'type' => PermissionFieldType::EMAIL->value,
            'is_global' => true,
        ]);

        $this->import = PermissionImport::create([
            'name' => 'Multi Email Import',
            'class_name' => MultiEmailTestImporter::class,
            'description' => 'Test',
            'is_active' => true,
        ]);

        ImportFieldMapping::create([
            'permission_import_id' => $this->import->id,
            'import_field_name' => 'corp_email',
            'permission_field_id' => $this->corpEmailField->id,
            'is_internal' => true,
        ]);
    }

    public function test_matches_user_by_personal_email_when_import_sends_corp(): void
    {
        $user = VirtualUser::create(['name' => 'Alice', 'status' => VirtualUserStatus::ACTIVE]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $this->personalEmailField->id,
            'value' => 'alice@home.com',
        ]);

        MultiEmailTestImporter::$usersToReturn = [
            ['external_id' => 'ext-1', 'corp_email' => 'alice@home.com'],
        ];
        $this->app->bind(MultiEmailTestImporter::class, fn () => new MultiEmailTestImporter);

        $action = app(FetchImportAction::class);
        $importRunId = $action->handle($this->import->id);

        $row = ImportStagingRow::where('import_run_id', $importRunId)
            ->where('external_id', 'ext-1')
            ->first();

        $this->assertSame(ImportMatchStatus::EXISTS, $row->match_status);
        $this->assertSame($user->id, $row->matched_virtual_user_id);
    }

    public function test_missing_excludes_users_matched_through_any_email_field(): void
    {
        $matchedUser = VirtualUser::create(['name' => 'Alice', 'status' => VirtualUserStatus::ACTIVE]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $matchedUser->id,
            'permission_field_id' => $this->personalEmailField->id,
            'value' => 'alice@home.com',
        ]);

        $absentUser = VirtualUser::create(['name' => 'Bob', 'status' => VirtualUserStatus::ACTIVE]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $absentUser->id,
            'permission_field_id' => $this->corpEmailField->id,
            'value' => 'bob@corp.com',
        ]);

        MultiEmailTestImporter::$usersToReturn = [
            ['external_id' => 'ext-1', 'corp_email' => 'alice@home.com'],
        ];
        $this->app->bind(MultiEmailTestImporter::class, fn () => new MultiEmailTestImporter);

        $action = app(FetchImportAction::class);
        $importRunId = $action->handle($this->import->id);

        $missing = ImportStagingRow::where('import_run_id', $importRunId)
            ->where('match_status', ImportMatchStatus::MISSING->value)
            ->get();

        $this->assertCount(1, $missing);
        $this->assertSame($absentUser->id, $missing->first()->matched_virtual_user_id);
    }
}

class MultiEmailTestImporter implements PermissionImportInterface
{
    public static array $usersToReturn = [];

    public function execute(ImportContext $context): ImportResult
    {
        return ImportResult::success(static::$usersToReturn);
    }

    public function getName(): string
    {
        return 'Multi Email Importer';
    }

    public function getDescription(): string
    {
        return 'Test';
    }

    public function getRequiredFields(): array
    {
        return [];
    }

    public function getConfigFields(): array
    {
        return [];
    }

    public function getRelatedTriggerClassPatterns(): array
    {
        return [];
    }

    public function getDepartmentFieldName(): string
    {
        return 'department';
    }

    public function getFallbackTriggerClass(): ?string
    {
        return null;
    }
}
