<?php

namespace ArcheeNic\PermissionRegistry\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GrantedPermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'permission' => new PermissionResource($this->whenLoaded('permission')),
            'status' => $this->status,
            'status_message' => $this->status_message,
            'enabled' => $this->enabled,
            'field_values' => GrantedPermissionFieldValueResource::collection($this->whenLoaded('fieldValues')),
            'granted_at' => $this->granted_at,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
