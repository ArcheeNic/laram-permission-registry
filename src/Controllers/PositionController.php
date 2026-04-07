<?php

namespace ArcheeNic\PermissionRegistry\Controllers;

use App\Http\Controllers\Controller;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use ArcheeNic\PermissionRegistry\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PositionController extends Controller
{
    public function index()
    {
        return view('permission-registry::positions.index');
    }

    public function show(Position $position)
    {
        $position->load(['permissions', 'groups', 'parent', 'children']);
        return view('permission-registry::positions.show', compact('position'));
    }

    public function create()
    {
        $positions = Position::all();
        $permissions = Permission::all();
        $groups = PermissionGroup::all();
        return view('permission-registry::positions.create', compact('positions', 'permissions', 'groups'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:positions,id',
            'permissions' => 'nullable|array',
            'groups' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $position = Position::create([
            'name' => $request->name,
            'description' => $request->description,
            'parent_id' => $request->parent_id,
        ]);

        if ($request->has('permissions')) {
            $position->permissions()->attach($request->permissions);
        }

        if ($request->has('groups')) {
            $position->groups()->attach($request->groups);
        }

        return redirect()->route('permission-registry::positions.index')
            ->with('success', __('permission-registry::Position created successfully'));
    }

    public function edit(Position $position)
    {
        $position->load(['permissions', 'groups']);
        $positions = Position::where('id', '!=', $position->id)->get();
        $permissions = Permission::all();
        $groups = PermissionGroup::all();
        return view('permission-registry::positions.edit', compact('position', 'positions', 'permissions', 'groups'));
    }

    public function update(Request $request, Position $position)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:positions,id',
            'permissions' => 'nullable|array',
            'groups' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Избегаем цикличного наследования
        if ($request->parent_id && $position->id == $request->parent_id) {
            return redirect()->back()->withErrors(['parent_id' => __('permission-registry::Cannot set position as its own parent')]);
        }

        $position->update([
            'name' => $request->name,
            'description' => $request->description,
            'parent_id' => $request->parent_id,
        ]);

        if ($request->has('permissions')) {
            $position->permissions()->sync($request->permissions);
        } else {
            $position->permissions()->detach();
        }

        if ($request->has('groups')) {
            $position->groups()->sync($request->groups);
        } else {
            $position->groups()->detach();
        }

        return redirect()->route('permission-registry::positions.show', $position)
            ->with('success', __('permission-registry::Position updated successfully'));
    }

    public function destroy(Position $position)
    {
        // Проверка на возможность удаления (например, нет ли связанных записей)
        $position->delete();

        return redirect()->route('permission-registry::positions.index')
            ->with('success', __('permission-registry::Position deleted successfully'));
    }
}
