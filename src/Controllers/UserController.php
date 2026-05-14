<?php

namespace ArcheeNic\PermissionRegistry\Controllers;

use App\Http\Controllers\Controller;
use ArcheeNic\PermissionRegistry\Actions\GrantPermissionAction;
use ArcheeNic\PermissionRegistry\Actions\RevokePermissionAction;
use ArcheeNic\PermissionRegistry\Enums\PermissionScope;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionResource;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        return view('permission-registry::users.index');
    }

    public function show(VirtualUser $user)
    {
        return view('permission-registry::users.show', compact('user'));
    }

    public function permissions(VirtualUser $user)
    {
        $grantedPermissions = GrantedPermission::where('virtual_user_id', $user->id)
            ->with(['permission', 'fieldValues.field', 'resource'])
            ->get();

        $fullyGrantedPermissionIds = $grantedPermissions
            ->whereNull('resource_id')
            ->pluck('permission_id')
            ->all();

        $availablePermissions = Permission::query()
            ->whereNotIn('id', $fullyGrantedPermissionIds)
            ->orderBy('service')
            ->orderBy('name')
            ->get();

        $permissionsWithFields = Permission::with('fields')->get()->map(function (Permission $permission) {
            $scope = $permission->scope ?? PermissionScope::Service;

            return [
                'id' => $permission->id,
                'service' => $permission->service,
                'name' => $permission->name,
                'scope' => $scope->value,
                'resource_kind' => $permission->resource_kind,
                'fields' => $permission->fields->map(fn ($field) => [
                    'id' => $field->id,
                    'name' => $field->name,
                    'default_value' => $field->default_value ?? null,
                ])->all(),
            ];
        })->all();

        $resourceCatalog = $this->buildResourceCatalog($availablePermissions);

        return view('permission-registry::users.permissions', compact(
            'user',
            'grantedPermissions',
            'availablePermissions',
            'permissionsWithFields',
            'resourceCatalog',
        ));
    }

    /**
     * @return array<int, array<int, array{id:int,name:string,external_id:?string}>>
     */
    private function buildResourceCatalog($permissions): array
    {
        $byKind = [];
        foreach ($permissions as $permission) {
            $scope = $permission->scope ?? PermissionScope::Service;
            if ($scope !== PermissionScope::Resource || ! $permission->resource_kind) {
                continue;
            }
            $byKind[$permission->service.'|'.$permission->resource_kind] = true;
        }

        if (empty($byKind)) {
            return [];
        }

        $catalog = [];
        foreach (array_keys($byKind) as $compound) {
            [$service, $kind] = explode('|', $compound, 2);
            $resources = PermissionResource::query()
                ->where('service', $service)
                ->where('kind', $kind)
                ->where('present_in_source', true)
                ->orderBy('name')
                ->get(['id', 'name', 'external_id'])
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'external_id' => $r->external_id,
                ])
                ->all();

            foreach ($permissions as $permission) {
                $scope = $permission->scope ?? PermissionScope::Service;
                if ($scope === PermissionScope::Resource
                    && $permission->service === $service
                    && $permission->resource_kind === $kind
                ) {
                    $catalog[$permission->id] = $resources;
                }
            }
        }

        return $catalog;
    }

    public function grantPermission(Request $request, VirtualUser $user)
    {
        $validator = Validator::make($request->all(), [
            'permission_id' => 'required|exists:permissions,id',
            'resource_ids' => 'nullable|array',
            'resource_ids.*' => 'integer|exists:permission_resources,id',
            'expires_at' => 'nullable|date',
            'fields' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $permission = Permission::findOrFail((int) $request->permission_id);
        $isResourceScoped = ($permission->scope ?? PermissionScope::Service) === PermissionScope::Resource;
        $resourceIds = array_values(array_unique(array_map('intval', (array) $request->input('resource_ids', []))));

        if ($isResourceScoped && empty($resourceIds)) {
            return redirect()->back()
                ->withErrors(['resource_ids' => __('permission-registry::Resources').': '.__('permission-registry::This field is required')])
                ->withInput();
        }

        $action = app(GrantPermissionAction::class);

        if ($isResourceScoped) {
            foreach ($resourceIds as $resourceId) {
                $action->handle(
                    userId: $user->id,
                    permissionId: $permission->id,
                    fieldValues: $request->fields ?? [],
                    meta: [],
                    expiresAt: $request->expires_at,
                    resourceId: $resourceId,
                );
            }
        } else {
            $action->handle(
                userId: $user->id,
                permissionId: $permission->id,
                fieldValues: $request->fields ?? [],
                meta: [],
                expiresAt: $request->expires_at,
                resourceId: null,
            );
        }

        return redirect()->route('permission-registry::users.permissions', $user)
            ->with('success', __('permission-registry::Право успешно выдано'));
    }

    public function revokePermission(VirtualUser $user, GrantedPermission $permission)
    {
        $action = app(RevokePermissionAction::class);
        $action->handle(
            userId: $user->id,
            permissionId: $permission->permission_id,
            resourceId: $permission->resource_id,
        );

        return redirect()->route('permission-registry::users.permissions', $user)
            ->with('success', __('permission-registry::Право успешно отозвано'));
    }
}
