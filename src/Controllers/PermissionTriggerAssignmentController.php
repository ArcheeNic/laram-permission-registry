<?php

namespace ArcheeNic\PermissionRegistry\Controllers;

use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionTrigger;
use ArcheeNic\PermissionRegistry\Models\PermissionTriggerAssignment;
use ArcheeNic\PermissionRegistry\Services\TriggerOverlapDetectorService;
use ArcheeNic\PermissionRegistry\Services\TriggerDiscoveryService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class PermissionTriggerAssignmentController extends Controller
{
    public function __construct(
        private TriggerDiscoveryService $triggerDiscoveryService,
        private TriggerOverlapDetectorService $triggerOverlapDetectorService
    ) {}

    public function index(Permission $permission)
    {
        $this->syncDiscoveredTriggers();

        $grantTriggers = $permission->grantTriggers()->with('trigger')->get();
        $revokeTriggers = $permission->revokeTriggers()->with('trigger')->get();
        $availableTriggers = PermissionTrigger::where('is_active', true)->get();
        $notConfiguredTriggerIds = $this->getNotConfiguredTriggerIds();
        $overlaps = $this->triggerOverlapDetectorService->detectOverlaps($permission->id);

        return view('permission-registry::permissions.triggers', compact(
            'permission',
            'grantTriggers',
            'revokeTriggers',
            'availableTriggers',
            'notConfiguredTriggerIds',
            'overlaps'
        ));
    }

    public function store(Request $request, Permission $permission)
    {
        $validator = Validator::make($request->all(), [
            'permission_trigger_id' => 'required|exists:permission_triggers,id',
            'event_type' => 'required|in:grant,revoke',
            'order' => 'required|integer|min:0',
            'is_enabled' => 'boolean',
            'config' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $assignment = PermissionTriggerAssignment::create([
            'permission_id' => $permission->id,
            ...$validator->validated(),
        ]);

        return response()->json([
            'success' => true,
            'assignment' => $assignment->load('trigger'),
        ]);
    }

    public function update(Request $request, Permission $permission, PermissionTriggerAssignment $assignment)
    {
        $validator = Validator::make($request->all(), [
            'order' => 'sometimes|integer|min:0',
            'is_enabled' => 'sometimes|boolean',
            'config' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $assignment->update($validator->validated());

        return response()->json([
            'success' => true,
            'assignment' => $assignment->load('trigger'),
        ]);
    }

    public function destroy(Permission $permission, PermissionTriggerAssignment $assignment)
    {
        $assignment->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Получить config_fields для триггера (для построения формы настроек в UI).
     */
    public function configFields(Permission $permission, PermissionTrigger $trigger)
    {
        $metadata = $this->triggerDiscoveryService->getTriggerMetadata($trigger->class_name);

        return response()->json([
            'success' => true,
            'config_fields' => $metadata['config_fields'] ?? [],
        ]);
    }

    public function reorder(Request $request, Permission $permission)
    {
        $validator = Validator::make($request->all(), [
            'event_type' => 'required|in:grant,revoke',
            'orders' => 'required|array',
            'orders.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        foreach ($request->orders as $assignmentId => $order) {
            PermissionTriggerAssignment::where('id', $assignmentId)
                ->where('permission_id', $permission->id)
                ->where('event_type', $request->event_type)
                ->update(['order' => $order]);
        }

        return response()->json(['success' => true]);
    }

    private function syncDiscoveredTriggers(): void
    {
        $discovered = $this->triggerDiscoveryService->discover();
        $table = (new PermissionTrigger)->getTable();
        $hasIsConfigured = Schema::hasColumn($table, PermissionTrigger::IS_CONFIGURED);

        foreach ($discovered as $item) {
            $createAttrs = [
                PermissionTrigger::NAME => $item['name'],
                PermissionTrigger::DESCRIPTION => $item['description'] ?? null,
                PermissionTrigger::TYPE => 'both',
                PermissionTrigger::IS_ACTIVE => true,
            ];
            if ($hasIsConfigured) {
                $createAttrs[PermissionTrigger::IS_CONFIGURED] = $item['is_configured'] ?? true;
            }
            $trigger = PermissionTrigger::firstOrCreate(
                [PermissionTrigger::CLASS_NAME => $item['class_name']],
                $createAttrs
            );
            if ($hasIsConfigured) {
                $trigger->update([PermissionTrigger::IS_CONFIGURED => $item['is_configured'] ?? true]);
            }
        }
    }

    /**
     * @return array<int>
     */
    private function getNotConfiguredTriggerIds(): array
    {
        $table = (new PermissionTrigger)->getTable();
        if (! Schema::hasColumn($table, PermissionTrigger::IS_CONFIGURED)) {
            return [];
        }

        return PermissionTrigger::where('is_active', true)->where('is_configured', false)->pluck('id')->toArray();
    }
}
