<?php

namespace ArcheeNic\PermissionRegistry\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'global_fields' => 'sometimes|array',
            'global_fields.*' => 'nullable|string',
        ];
    }
}
