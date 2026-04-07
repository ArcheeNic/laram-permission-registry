<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\SaveTriggerFieldMappingAction;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\PermissionTrigger;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class SaveTriggerFieldMappingActionTest extends TestCase
{
    public function test_handle_clears_cached_mapping_for_trigger(): void
    {
        $field = PermissionField::create(['name' => 'email']);
        $trigger = PermissionTrigger::create([
            'name' => 'Bitrix24 Add',
            'class_name' => 'App\\Triggers\\Bitrix24AddToDepartmentTrigger',
        ]);

        $cacheKey = "trigger_field_mapping_{$trigger->id}";
        Cache::put($cacheKey, ['email' => ['permission_field_id' => 999]], 3600);

        /** @var SaveTriggerFieldMappingAction $action */
        $action = app(SaveTriggerFieldMappingAction::class);
        $saved = $action->handle($trigger->id, ['email' => (string) $field->id]);

        $this->assertTrue($saved);
        $this->assertNull(Cache::get($cacheKey));
    }
}
