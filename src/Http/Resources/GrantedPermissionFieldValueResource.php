<?php

namespace ArcheeNic\PermissionRegistry\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GrantedPermissionFieldValueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'field_id' => $this->permission_field_id,
            'field_name' => $this->field?->name,
            'value' => $this->value,
        ];
    }
}
