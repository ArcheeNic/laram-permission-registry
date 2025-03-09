<?php

namespace App\Modules\PermissionRegistry\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PermissionRegistry\Models\Permission;
use App\Modules\PermissionRegistry\Models\PermissionGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermissionGroupController extends Controller
{
    public function index()
    {
        return view('permission-registry::groups.index');
    }

    public function show(PermissionGroup $group)
    {
        $group->load(['permissions']);
        return view('permission-registry::groups.show', compact('group'));
    }

    public function create()
    {
        $permissions = Permission::all();
        return view('permission-registry::groups.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $group = PermissionGroup::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        if ($request->has('permissions')) {
            $group->permissions()->attach($request->permissions);
        }

        return redirect()->route('permission-registry::groups.index')
            ->with('success', __('permission-registry::Group created successfully'));
    }

    public function edit(PermissionGroup $group)
    {
        $group->load(['permissions']);
        $permissions = Permission::all();
        return view('permission-registry::groups.edit', compact('group', 'permissions'));
    }

    public function update(Request $request, PermissionGroup $group)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $group->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        if ($request->has('permissions')) {
            $group->permissions()->sync($request->permissions);
        } else {
            $group->permissions()->detach();
        }

        return redirect()->route('permission-registry::groups.show', $group)
            ->with('success', __('permission-registry::Group updated successfully'));
    }

    public function destroy(PermissionGroup $group)
    {
        // Проверка на возможность удаления (например, нет ли связанных записей)
        $group->delete();

        return redirect()->route('permission-registry::groups.index')
            ->with('success', __('permission-registry::Group deleted successfully'));
    }
}
