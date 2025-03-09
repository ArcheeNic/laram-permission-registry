<?php

namespace App\Modules\PermissionRegistry\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PermissionRegistry\Models\Permission;
use App\Modules\PermissionRegistry\Models\PermissionField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    public function index()
    {
        return view('permission-registry::permissions.index');
    }

    public function show(Permission $permission)
    {
        $permission->load(['fields', 'groups']);
        return view('permission-registry::permissions.show', compact('permission'));
    }

    public function create()
    {
        $fields = PermissionField::all();
        return view('permission-registry::permissions.create', compact('fields'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
            'fields' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $permission = Permission::create([
            'service' => $request->service,
            'name' => $request->name,
            'description' => $request->description,
            'tags' => $request->tags ?? [],
        ]);

        if ($request->has('fields')) {
            $permission->fields()->attach($request->fields);
        }

        return redirect()->route('permission-registry::permissions.show', $permission)
            ->with('success', __('permission-registry::Permission created successfully'));
    }

    public function edit(Permission $permission)
    {
        $permission->load(['fields', 'groups']);
        $fields = PermissionField::all();
        return view('permission-registry::permissions.edit', compact('permission', 'fields'));
    }

    public function update(Request $request, Permission $permission)
    {
        $validator = Validator::make($request->all(), [
            'service' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
            'fields' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $permission->update([
            'service' => $request->service,
            'name' => $request->name,
            'description' => $request->description,
            'tags' => $request->tags ?? [],
        ]);

        if ($request->has('fields')) {
            $permission->fields()->sync($request->fields);
        } else {
            $permission->fields()->detach();
        }

        return redirect()->route('permission-registry::permissions.show', $permission)
            ->with('success', __('permission-registry::Permission updated successfully'));
    }

    public function destroy(Permission $permission)
    {
        // Проверка на возможность удаления (например, нет ли связанных записей)
        $permission->delete();

        return redirect()->route('permission-registry::index')
            ->with('success', __('permission-registry::Permission deleted successfully'));
    }
}
