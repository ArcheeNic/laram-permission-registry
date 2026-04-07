<?php

namespace ArcheeNic\PermissionRegistry\Controllers;

use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use ArcheeNic\PermissionRegistry\Models\HrEventTriggerAssignment;
use ArcheeNic\PermissionRegistry\Models\PermissionTrigger;
use ArcheeNic\PermissionRegistry\Services\TriggerDiscoveryService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HrEventTriggerAssignmentController extends Controller
{
    public function __construct(
        private TriggerDiscoveryService $triggerDiscoveryService
    ) {
    }

    public function index()
    {
        $this->syncDiscoveredTriggers();

        $categories = EmployeeCategory::cases();
        $triggersByCategory = [];
        foreach ($categories as $category) {
            $triggersByCategory[$category->value] = [
                'hire' => HrEventTriggerAssignment::query()
                    ->where('event_type', 'hire')
                    ->forCategory($category)
                    ->with('trigger')
                    ->orderBy('order')
                    ->get(),
                'fire' => HrEventTriggerAssignment::query()
                    ->where('event_type', 'fire')
                    ->forCategory($category)
                    ->with('trigger')
                    ->orderBy('order')
                    ->get(),
            ];
        }

        $availableTriggers = PermissionTrigger::query()
            ->where('is_active', true)
            ->get();

        $notConfiguredTriggerIds = $this->getNotConfiguredTriggerIds();

        return view('permission-registry::hr-triggers.index', compact(
            'categories',
            'triggersByCategory',
            'availableTriggers',
            'notConfiguredTriggerIds'
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'permission_trigger_id' => [
                'required',
                Rule::exists('permission_triggers', 'id')->where('is_active', true),
            ],
            'event_type' => 'required|in:hire,fire',
            'employee_category' => 'required|in:' . implode(',', array_column(EmployeeCategory::cases(), 'value')),
            'order' => 'required|integer|min:0',
            'is_enabled' => 'boolean',
            'config' => 'nullable|array',
        ]);
        $validator->sometimes('permission_trigger_id', [
            Rule::unique('hr_event_trigger_assignments', 'permission_trigger_id')
                ->where(fn ($query) => $query
                    ->where('event_type', $request->input('event_type'))
                    ->where('employee_category', $request->input('employee_category'))),
        ], static fn () => true);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $assignment = HrEventTriggerAssignment::create($validator->validated());

        return response()->json([
            'success' => true,
            'assignment' => $assignment->load('trigger'),
        ]);
    }

    public function update(Request $request, HrEventTriggerAssignment $assignment)
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

    public function destroy(HrEventTriggerAssignment $assignment)
    {
        $assignment->delete();

        return response()->json(['success' => true]);
    }

    public function configFields(PermissionTrigger $trigger)
    {
        $metadata = $this->triggerDiscoveryService->getTriggerMetadata($trigger->class_name);

        return response()->json([
            'success' => true,
            'config_fields' => $metadata['config_fields'] ?? [],
        ]);
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

        return PermissionTrigger::query()
            ->where('is_active', true)
            ->where('is_configured', false)
            ->pluck('id')
            ->toArray();
    }
}
