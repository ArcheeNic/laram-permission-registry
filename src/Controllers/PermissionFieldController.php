<?php

namespace ArcheeNic\PermissionRegistry\Controllers;

use App\Http\Controllers\Controller;
use ArcheeNic\PermissionRegistry\Enums\PermissionFieldType;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
            'type' => ['nullable', 'string', Rule::in(array_column(PermissionFieldType::cases(), 'value'))],
            'default_value' => 'nullable|string|max:255',
            'is_global' => 'nullable|boolean',
            'required_on_user_create' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $field = PermissionField::create([
            'name' => $request->name,
            'type' => $request->input('type', PermissionFieldType::STRING->value),
            'default_value' => $request->default_value,
            'is_global' => $request->boolean('is_global', false),
            'required_on_user_create' => $request->boolean('required_on_user_create', false),
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
            'type' => ['nullable', 'string', Rule::in(array_column(PermissionFieldType::cases(), 'value'))],
            'default_value' => 'nullable|string|max:255',
            'is_global' => 'nullable|boolean',
            'required_on_user_create' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $field->update([
            'name' => $request->name,
            'type' => $request->input('type', PermissionFieldType::STRING->value),
            'default_value' => $request->default_value,
            'is_global' => $request->boolean('is_global', false),
            'required_on_user_create' => $request->boolean('required_on_user_create', false),
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
