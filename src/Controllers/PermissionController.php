<?php

namespace ArcheeNic\PermissionRegistry\Controllers;

use App\Http\Controllers\Controller;
use ArcheeNic\PermissionRegistry\Actions\CopyPermissionAction;
use ArcheeNic\PermissionRegistry\Actions\DeletePermissionAction;
use ArcheeNic\PermissionRegistry\Exceptions\PermissionCannotBeDeletedException;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
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
        $permission->load(['fields', 'groups', 'positions.parent']);
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
            'auto_grant' => 'sometimes|boolean',
            'auto_revoke' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $permission = Permission::create([
            'service' => $request->service,
            'name' => $request->name,
            'description' => $request->description,
            'tags' => $request->tags ?? [],
            'auto_grant' => $request->boolean('auto_grant'),
            'auto_revoke' => $request->boolean('auto_revoke'),
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
            'auto_grant' => 'sometimes|boolean',
            'auto_revoke' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $permission->update([
            'service' => $request->service,
            'name' => $request->name,
            'description' => $request->description,
            'tags' => $request->tags ?? [],
            'auto_grant' => $request->boolean('auto_grant'),
            'auto_revoke' => $request->boolean('auto_revoke'),
        ]);

        if ($request->has('fields')) {
            $permission->fields()->sync($request->fields);
        } else {
            $permission->fields()->detach();
        }

        return redirect()->route('permission-registry::permissions.show', $permission)
            ->with('success', __('permission-registry::Permission updated successfully'));
    }

    public function destroy(Permission $permission, DeletePermissionAction $action)
    {
        try {
            $action->handle($permission);
        } catch (PermissionCannotBeDeletedException $exception) {
            return redirect()->back()
                ->with('error', $exception->getUserMessage());
        }

        return redirect()->route('permission-registry::index')
            ->with('success', __('permission-registry::Permission deleted successfully'));
    }

    public function copy(Permission $permission, CopyPermissionAction $action)
    {
        $copy = $action->handle($permission);

        return redirect()->route('permission-registry::permissions.edit', $copy)
            ->with('success', __('permission-registry::Permission copied successfully'));
    }
}
