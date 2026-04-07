<?php

namespace ArcheeNic\PermissionRegistry\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VirtualUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'positions' => PositionResource::collection($this->whenLoaded('positions')),
            'groups' => PermissionGroupResource::collection($this->whenLoaded('groups')),
            'granted_permissions' => GrantedPermissionResource::collection($this->whenLoaded('grantedPermissions')),
            'field_values' => VirtualUserFieldValueResource::collection($this->whenLoaded('fieldValues')),
        ];
    }
}
