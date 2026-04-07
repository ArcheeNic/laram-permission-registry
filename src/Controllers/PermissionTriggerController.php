<?php

namespace ArcheeNic\PermissionRegistry\Controllers;

use ArcheeNic\PermissionRegistry\Actions\SaveTriggerFieldMappingAction;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\PermissionTrigger;
use ArcheeNic\PermissionRegistry\Services\TriggerDiscoveryService;
use ArcheeNic\PermissionRegistry\Services\TriggerFieldMappingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class PermissionTriggerController extends Controller
{
    public function __construct(
        private TriggerDiscoveryService $discoveryService,
        private TriggerFieldMappingService $mappingService,
        private SaveTriggerFieldMappingAction $saveMappingAction
    ) {
    }
    public function index()
    {
        $triggers = PermissionTrigger::orderBy('name')->paginate(20);
        
        return view('permission-registry::triggers.index', compact('triggers'));
    }

    public function create()
    {
        $globalFields = PermissionField::where('is_global', true)->orderBy('name')->get();
        
        return view('permission-registry::triggers.create', compact('globalFields'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permission_triggers',
            'class_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:grant,revoke,both',
            'is_active' => 'boolean',
            'mapping' => 'nullable|array',
            'mapping.*' => 'required|string|max:255',
            'internal_mapping' => 'nullable|array',
            'internal_mapping.*' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Получить метаданные триггера для проверки обязательности маппинга
        $metadata = $this->discoveryService->getTriggerMetadata($request->input('class_name'));
        $requiredFields = collect($metadata['required_fields'] ?? []);

        // Проверить что все входящие поля замаплены
        $inputFields = $requiredFields->filter(fn($f) => !($f['is_internal'] ?? false));
        foreach ($inputFields as $field) {
            $mappingValue = $request->input("mapping.{$field['name']}");
            if (empty($mappingValue)) {
                $validator->errors()->add("mapping.{$field['name']}", "Поле {$field['name']} обязательно для маппинга");
            }
        }

        // Проверить что все внутренние поля замаплены
        $internalFields = $requiredFields->filter(fn($f) => $f['is_internal'] ?? false);
        foreach ($internalFields as $field) {
            $mappingValue = $request->input("internal_mapping.{$field['name']}");
            if (empty($mappingValue)) {
                $validator->errors()->add("internal_mapping.{$field['name']}", "Поле {$field['name']} обязательно для маппинга");
            }
        }

        // Если есть ошибки валидации маппинга, вернуться назад
        if ($validator->errors()->isNotEmpty()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $trigger = PermissionTrigger::create($validator->validated());

        // Сохранить маппинг полей, если он был передан
        if ($request->has('mapping') || $request->has('internal_mapping')) {
            $this->saveMappingAction->handle(
                $trigger->id,
                $request->input('mapping', []),
                $request->input('internal_mapping', [])
            );
        }

        return redirect()->route('permission-registry::triggers.index')
            ->with('success', 'Триггер создан успешно');
    }

    public function edit(PermissionTrigger $permissionTrigger)
    {
        // Загрузить метаданные и маппинг для триггера
        $metadata = $this->discoveryService->getTriggerMetadata($permissionTrigger->class_name);
        $currentMapping = $this->mappingService->getMapping($permissionTrigger->id);
        $globalFields = PermissionField::where('is_global', true)->orderBy('name')->get();
        
        return view('permission-registry::triggers.edit', compact('permissionTrigger', 'metadata', 'currentMapping', 'globalFields'));
    }

    public function update(Request $request, PermissionTrigger $permissionTrigger)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permission_triggers,name,' . $permissionTrigger->id,
            'class_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:grant,revoke,both',
            'is_active' => 'boolean',
            'mapping' => 'nullable|array',
            'mapping.*' => 'required|string|max:255',
            'internal_mapping' => 'nullable|array',
            'internal_mapping.*' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Получить метаданные триггера для проверки обязательности маппинга
        $metadata = $this->discoveryService->getTriggerMetadata($permissionTrigger->class_name);
        $requiredFields = collect($metadata['required_fields'] ?? []);

        // Проверить что все входящие поля замаплены
        $inputFields = $requiredFields->filter(fn($f) => !($f['is_internal'] ?? false));
        foreach ($inputFields as $field) {
            $mappingValue = $request->input("mapping.{$field['name']}");
            if (empty($mappingValue)) {
                $validator->errors()->add("mapping.{$field['name']}", "Поле {$field['name']} обязательно для маппинга");
            }
        }

        // Проверить что все внутренние поля замаплены
        $internalFields = $requiredFields->filter(fn($f) => $f['is_internal'] ?? false);
        foreach ($internalFields as $field) {
            $mappingValue = $request->input("internal_mapping.{$field['name']}");
            if (empty($mappingValue)) {
                $validator->errors()->add("internal_mapping.{$field['name']}", "Поле {$field['name']} обязательно для маппинга");
            }
        }

        // Если есть ошибки валидации маппинга, вернуться назад
        if ($validator->errors()->isNotEmpty()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $permissionTrigger->update($validator->validated());

        // Сохранить маппинг полей, если он был передан
        if ($request->has('mapping') || $request->has('internal_mapping')) {
            $this->saveMappingAction->handle(
                $permissionTrigger->id,
                $request->input('mapping', []),
                $request->input('internal_mapping', [])
            );
            $this->mappingService->clearCache($permissionTrigger->id);
        }

        return redirect()->route('permission-registry::triggers.index')
            ->with('success', 'Триггер и маппинг полей обновлены успешно');
    }

    public function destroy(PermissionTrigger $permissionTrigger)
    {
        $permissionTrigger->delete();

        return redirect()->route('permission-registry::triggers.index')
            ->with('success', 'Триггер удален успешно');
    }

    /**
     * API endpoint для получения списка доступных триггеров
     */
    public function discover()
    {
        $triggers = $this->discoveryService->discover();

        return response()->json([
            'success' => true,
            'triggers' => $triggers,
        ]);
    }

    /**
     * API endpoint для получения метаданных конкретного триггера
     */
    public function metadata(Request $request)
    {
        $className = $request->input('class_name');

        if (!$className) {
            return response()->json([
                'success' => false,
                'message' => 'Class name is required',
            ], 400);
        }

        $metadata = $this->discoveryService->getTriggerMetadata($className);

        if (!$metadata) {
            return response()->json([
                'success' => false,
                'message' => 'Trigger not found or invalid',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'metadata' => $metadata,
        ]);
    }
}
