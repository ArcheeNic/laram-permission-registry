<?php

namespace ArcheeNic\PermissionRegistry\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_id' => 'required|exists:permission_groups,id',
        ];
    }
}
