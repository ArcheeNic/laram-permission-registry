<?php

namespace ArcheeNic\PermissionRegistry\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GrantPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permission_id' => 'required|exists:permissions,id',
            'field_values' => 'sometimes|array',
            'field_values.*' => 'nullable|string',
            'meta' => 'sometimes|array',
            'expires_at' => 'nullable|date',
            'skip_triggers' => 'sometimes|boolean',
            'execute_sync' => 'sometimes|boolean',
        ];
    }
}
