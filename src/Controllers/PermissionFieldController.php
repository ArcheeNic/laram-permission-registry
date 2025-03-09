<?php

namespace App\Modules\PermissionRegistry\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PermissionRegistry\Models\PermissionField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermissionFieldController extends Controller
{
    public function index()
    {
        return view('permission-registry::fields.index');
    }

    public function create()
    {
        return view('permission-registry::fields.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'default_value' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $field = PermissionField::create([
            'name' => $request->name,
            'default_value' => $request->default_value,
        ]);

        return redirect()->route('permission-registry::fields.index')
            ->with('success', __('permission-registry::Field created successfully'));
    }

    public function edit(PermissionField $field)
    {
        return view('permission-registry::fields.edit', compact('field'));
    }

    public function update(Request $request, PermissionField $field)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'default_value' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $field->update([
            'name' => $request->name,
            'default_value' => $request->default_value,
        ]);

        return redirect()->route('permission-registry::fields.index')
            ->with('success', __('permission-registry::Field updated successfully'));
    }

    public function destroy(PermissionField $field)
    {
        // Проверка на возможность удаления (например, нет ли связанных записей)
        $field->delete();

        return redirect()->route('permission-registry::fields.index')
            ->with('success', __('permission-registry::Field deleted successfully'));
    }
}
