<?php

namespace ArcheeNic\PermissionRegistry\Controllers;

use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionDependency;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class PermissionDependencyController extends Controller
{
    public function index(Permission $permission)
    {
        return view('permission-registry::permissions.dependencies', compact('permission'));
    }

    public function store(Request $request, Permission $permission)
    {
        $validator = Validator::make($request->all(), [
            'required_permission_id' => 'required|exists:permissions,id',
            'is_strict' => 'boolean',
            'event_type' => 'required|in:grant,revoke',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Проверка на циклическую зависимость
        if ($this->wouldCreateCircularDependency($permission->id, $request->required_permission_id, $request->event_type)) {
            return response()->json([
                'errors' => ['required_permission_id' => ['Циклическая зависимость запрещена']]
            ], 422);
        }

        $dependency = PermissionDependency::create([
            'permission_id' => $permission->id,
            ...$validator->validated(),
        ]);

        return response()->json([
            'success' => true,
            'dependency' => $dependency->load('requiredPermission'),
        ]);
    }

    public function update(Request $request, Permission $permission, PermissionDependency $dependency)
    {
        $validator = Validator::make($request->all(), [
            'is_strict' => 'boolean',
            'event_type' => 'in:grant,revoke',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $dependency->update($validator->validated());

        return response()->json([
            'success' => true,
            'dependency' => $dependency->load('requiredPermission'),
        ]);
    }

    public function destroy(Permission $permission, PermissionDependency $dependency)
    {
        $dependency->delete();

        return response()->json(['success' => true]);
    }

    private function wouldCreateCircularDependency(int $permissionId, int $requiredPermissionId, string $eventType = 'grant'): bool
    {
        // Простая проверка: если требуемое право зависит от текущего с тем же event_type, это циклическая зависимость
        $exists = PermissionDependency::where('permission_id', $requiredPermissionId)
            ->where('required_permission_id', $permissionId)
            ->where('event_type', $eventType)
            ->exists();

        if ($exists) {
            return true;
        }

        // Более глубокая проверка можно добавить при необходимости
        return false;
    }
}
