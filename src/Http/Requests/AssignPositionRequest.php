<?php

namespace ArcheeNic\PermissionRegistry\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'position_id' => 'required|exists:positions,id',
        ];
    }
}
