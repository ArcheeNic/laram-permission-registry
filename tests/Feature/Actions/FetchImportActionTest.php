<?php

namespace ArcheeNic\PermissionRegistry\Tests\Feature\Actions;

use ArcheeNic\PermissionRegistry\Actions\FetchImportAction;
use ArcheeNic\PermissionRegistry\Contracts\PermissionImportInterface;
use ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus;
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
use Illuminate\Support\Str;

class FetchImportActionTest extends TestCase
{
    private PermissionField $emailField;

    private PermissionImport $import;

    protected function setUp(): void
    {
        parent::setUp();

        $this->emailField = PermissionField::create([
            'name' => 'email',
            'is_global' => true,
        ]);

        $this->import = PermissionImport::create([
            'name' => 'Test Import',
            'class_name' => FetchImportTestImporter::class,
            'description' => 'Test',
            'is_active' => true,
        ]);

        ImportFieldMapping::create([
            'permission_import_id' => $this->import->id,
            'import_field_name' => 'email',
            'permission_field_id' => $this->emailField->id,
            'is_internal' => true,
        ]);
    }

    public function test_fetch_creates_staging_rows_for_new_users(): void
    {
        $this->registerImporter([
            ['external_id' => 'ext-1', 'email' => 'alice@test.com'],
            ['external_id' => 'ext-2', 'email' => 'bob@test.com'],
        ]);

        $action = app(FetchImportAction::class);
        $importRunId = $action->handle($this->import->id);

        $this->assertTrue(Str::isUuid($importRunId));

        $rows = ImportStagingRow::where('import_run_id', $importRunId)->get();
        $this->assertCount(2, $rows);

        $newRows = $rows->where('match_status', ImportMatchStatus::NEW);
        $this->assertCount(2, $newRows);

        $this->assertNull($newRows->first()->matched_virtual_user_id);
    }

    public function test_fetch_marks_existing_users_as_exists_when_data_matches(): void
    {
        $user = VirtualUser::create(['name' => 'Alice', 'status' => VirtualUserStatus::ACTIVE]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $this->emailField->id,
            'value' => 'alice@test.com',
        ]);

        $this->registerImporter([
            ['external_id' => 'ext-1', 'email' => 'alice@test.com'],
        ]);

        $action = app(FetchImportAction::class);
        $importRunId = $action->handle($this->import->id);

        $row = ImportStagingRow::where('import_run_id', $importRunId)
            ->where('external_id', 'ext-1')
            ->first();
        $this->assertSame(ImportMatchStatus::EXISTS, $row->match_status);
        $this->assertSame($user->id, $row->matched_virtual_user_id);
    }

    public function test_fetch_marks_changed_when_data_differs(): void
    {
        $firstNameField = PermissionField::create(['name' => 'first_name', 'is_global' => true]);
        ImportFieldMapping::create([
            'permission_import_id' => $this->import->id,
            'import_field_name' => 'first_name',
            'permission_field_id' => $firstNameField->id,
            'is_internal' => false,
        ]);

        $user = VirtualUser::create(['name' => 'Alice', 'status' => VirtualUserStatus::ACTIVE]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $this->emailField->id,
            'value' => 'alice@test.com',
        ]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $user->id,
            'permission_field_id' => $firstNameField->id,
            'value' => 'Alice',
        ]);

        $this->registerImporter([
            ['external_id' => 'ext-1', 'email' => 'alice@test.com', 'first_name' => 'Alicia'],
        ]);

        $action = app(FetchImportAction::class);
        $importRunId = $action->handle($this->import->id);

        $row = ImportStagingRow::where('import_run_id', $importRunId)
            ->where('external_id', 'ext-1')
            ->first();
        $this->assertSame(ImportMatchStatus::CHANGED, $row->match_status);
        $this->assertSame($user->id, $row->matched_virtual_user_id);
    }

    public function test_fetch_creates_missing_rows_for_active_users_absent_in_import(): void
    {
        $presentUser = VirtualUser::create(['name' => 'Alice', 'status' => VirtualUserStatus::ACTIVE]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $presentUser->id,
            'permission_field_id' => $this->emailField->id,
            'value' => 'alice@test.com',
        ]);

        $absentUser = VirtualUser::create(['name' => 'Bob', 'status' => VirtualUserStatus::ACTIVE]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $absentUser->id,
            'permission_field_id' => $this->emailField->id,
            'value' => 'bob@test.com',
        ]);

        $this->registerImporter([
            ['external_id' => 'ext-1', 'email' => 'alice@test.com'],
        ]);

        $action = app(FetchImportAction::class);
        $importRunId = $action->handle($this->import->id);

        $missingRows = ImportStagingRow::where('import_run_id', $importRunId)
            ->where('match_status', ImportMatchStatus::MISSING->value)
            ->get();

        $this->assertCount(1, $missingRows);
        $this->assertSame($absentUser->id, $missingRows->first()->matched_virtual_user_id);
    }

    public function test_missing_rows_contain_current_user_field_values(): void
    {
        $firstNameField = PermissionField::create(['name' => 'first_name', 'is_global' => true]);
        $lastNameField = PermissionField::create(['name' => 'last_name', 'is_global' => true]);

        ImportFieldMapping::create([
            'permission_import_id' => $this->import->id,
            'import_field_name' => 'first_name',
            'permission_field_id' => $firstNameField->id,
            'is_internal' => false,
        ]);
        ImportFieldMapping::create([
            'permission_import_id' => $this->import->id,
            'import_field_name' => 'last_name',
            'permission_field_id' => $lastNameField->id,
            'is_internal' => false,
        ]);

        $absentUser = VirtualUser::create(['name' => 'Bob Smith', 'status' => VirtualUserStatus::ACTIVE]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $absentUser->id,
            'permission_field_id' => $this->emailField->id,
            'value' => 'bob@test.com',
        ]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $absentUser->id,
            'permission_field_id' => $firstNameField->id,
            'value' => 'Bob',
        ]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $absentUser->id,
            'permission_field_id' => $lastNameField->id,
            'value' => 'Smith',
        ]);

        $this->registerImporter([
            ['external_id' => 'ext-1', 'email' => 'other@test.com'],
        ]);

        $action = app(FetchImportAction::class);
        $importRunId = $action->handle($this->import->id);

        $missingRow = ImportStagingRow::where('import_run_id', $importRunId)
            ->where('match_status', ImportMatchStatus::MISSING->value)
            ->first();

        $this->assertNotNull($missingRow);
        $fields = $missingRow->fields;
        $this->assertIsArray($fields);
        $this->assertSame('bob@test.com', $fields['email']);
        $this->assertSame('Bob', $fields['first_name']);
        $this->assertSame('Smith', $fields['last_name']);
    }

    public function test_fetch_does_not_create_missing_rows_for_deactivated_users(): void
    {
        $deactivatedUser = VirtualUser::create([
            'name' => 'Charlie',
            'status' => VirtualUserStatus::DEACTIVATED,
        ]);
        VirtualUserFieldValue::create([
            'virtual_user_id' => $deactivatedUser->id,
            'permission_field_id' => $this->emailField->id,
            'value' => 'charlie@test.com',
        ]);

        $this->registerImporter([
            ['external_id' => 'ext-1', 'email' => 'new@test.com'],
        ]);

        $action = app(FetchImportAction::class);
        $importRunId = $action->handle($this->import->id);

        $missingRows = ImportStagingRow::where('import_run_id', $importRunId)
            ->where('match_status', ImportMatchStatus::MISSING->value)
            ->get();

        $this->assertCount(0, $missingRows);
    }

    public function test_parallel_runs_do_not_interfere(): void
    {
        $this->registerImporter([
            ['external_id' => 'ext-1', 'email' => 'a@test.com'],
        ]);

        $action = app(FetchImportAction::class);
        $runId1 = $action->handle($this->import->id);
        $runId2 = $action->handle($this->import->id);

        $this->assertNotEquals($runId1, $runId2);

        $this->assertSame(1, ImportStagingRow::where('import_run_id', $runId1)->count());
        $this->assertSame(1, ImportStagingRow::where('import_run_id', $runId2)->count());
    }

    private function registerImporter(array $users): void
    {
        FetchImportTestImporter::$usersToReturn = $users;
        $this->app->bind(FetchImportTestImporter::class, fn () => new FetchImportTestImporter());
    }
}

class FetchImportTestImporter implements PermissionImportInterface
{
    public static array $usersToReturn = [];

    public function execute(ImportContext $context): ImportResult
    {
        return ImportResult::success(static::$usersToReturn);
    }

    public function getName(): string
    {
        return 'Test Importer';
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
        return ['App\\Triggers\\Bitrix24%'];
    }

    public function getDepartmentFieldName(): string
    {
        return 'department_ids';
    }
}
