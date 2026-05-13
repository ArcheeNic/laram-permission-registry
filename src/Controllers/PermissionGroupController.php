<?php

namespace ArcheeNic\PermissionRegistry\Controllers;

use App\Http\Controllers\Controller;
use ArcheeNic\PermissionRegistry\Enums\PermissionScope;
use ArcheeNic\PermissionRegistry\Jobs\ApplyMembershipResourceDiffJob;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use ArcheeNic\PermissionRegistry\Models\PermissionGroupResource;
use ArcheeNic\PermissionRegistry\Models\PermissionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PermissionGroupController extends Controller
{

    public function index()
    {
        return view('permission-registry::groups.index');
    }

    public function show(PermissionGroup $group)
    {
        $group->load(['permissions', 'permissionResources.resource']);
        $resourcesByPermission = $group->permissionResources
            ->groupBy('permission_id')
            ->map(fn ($rows) => $rows->map(fn ($r) => $r->resource)->filter()->values());
        return view('permission-registry::groups.show', compact('group', 'resourcesByPermission'));
    }

    public function create()
    {
        $permissions = Permission::all();
        $resourceCatalog = $this->buildResourceCatalog($permissions);
        $selectedResources = [];
        return view('permission-registry::groups.create', compact('permissions', 'resourceCatalog', 'selectedResources'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permission_resources' => 'nullable|array',
            'permission_resources.*' => 'array',
            'permission_resources.*.*' => 'integer|exists:permission_resources,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $group = PermissionGroup::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        if ($request->has('permissions')) {
            $group->permissions()->attach($request->permissions);
        }

        $this->syncPermissionResources($group, $request->input('permission_resources', []));

        return redirect()->route('permission-registry::groups.index')
            ->with('success', __('permission-registry::Group created successfully'));
    }

    public function edit(PermissionGroup $group)
    {
        $group->load(['permissions', 'permissionResources']);
        $permissions = Permission::all();
        $resourceCatalog = $this->buildResourceCatalog($permissions);
        $selectedResources = $group->permissionResources
            ->groupBy('permission_id')
            ->map(fn ($rows) => $rows->pluck('resource_id')->all())
            ->toArray();

        return view('permission-registry::groups.edit', compact('group', 'permissions', 'resourceCatalog', 'selectedResources'));
    }

    public function update(Request $request, PermissionGroup $group)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permission_resources' => 'nullable|array',
            'permission_resources.*' => 'array',
            'permission_resources.*.*' => 'integer|exists:permission_resources,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $diff = DB::transaction(function () use ($group, $request): array {
            $group->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            $oldPairs = $this->loadGroupPairs($group->id);

            if ($request->has('permissions')) {
                $group->permissions()->sync((array) $request->input('permissions', []));
            } else {
                $group->permissions()->detach();
            }

            $this->syncPermissionResources($group, $request->input('permission_resources', []));

            $newPairs = $this->loadGroupPairs($group->id);

            return [
                'added' => array_values(array_diff_key($newPairs, $oldPairs)),
                'removed' => array_values(array_diff_key($oldPairs, $newPairs)),
                'userIds' => $this->affectedUserIds($group),
            ];
        });

        if (!empty($diff['userIds']) && (!empty($diff['added']) || !empty($diff['removed']))) {
            ApplyMembershipResourceDiffJob::dispatch(
                'group',
                $group->id,
                $diff['added'],
                $diff['removed'],
                $diff['userIds'],
            )->afterCommit();
        }

        return redirect()->route('permission-registry::groups.show', $group)
            ->with('success', __('permission-registry::Group updated successfully'));
    }

    public function destroy(PermissionGroup $group)
    {
        $group->delete();

        return redirect()->route('permission-registry::groups.index')
            ->with('success', __('permission-registry::Group deleted successfully'));
    }

    /**
     * @return array<int, \Illuminate\Support\Collection<\ArcheeNic\PermissionRegistry\Models\PermissionResource>>
     */
    private function buildResourceCatalog($permissions): array
    {
        $byKind = [];
        foreach ($permissions as $permission) {
            $scope = $permission->scope ?? PermissionScope::Service;
            if ($scope !== PermissionScope::Resource || !$permission->resource_kind) {
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
                ->get();

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

    private function syncPermissionResources(PermissionGroup $group, array $input): void
    {
        $permissions = $group->permissions()
            ->whereIn('permissions.id', array_keys($input))
            ->get()
            ->keyBy('id');

        $resourceIdsAll = collect($input)->flatten()->map(fn ($id) => (int) $id)->unique()->all();
        $resources = empty($resourceIdsAll)
            ? collect()
            : PermissionResource::query()->whereIn('id', $resourceIdsAll)->get()->keyBy('id');

        $desired = [];
        foreach ($input as $permissionId => $resourceIds) {
            $permissionId = (int) $permissionId;
            $permission = $permissions[$permissionId] ?? null;
            if (!$permission) {
                continue;
            }
            $scope = $permission->scope ?? PermissionScope::Service;
            if ($scope !== PermissionScope::Resource || !$permission->resource_kind) {
                continue;
            }

            foreach ((array) $resourceIds as $resourceId) {
                $resourceId = (int) $resourceId;
                $resource = $resources[$resourceId] ?? null;
                if (!$resource) {
                    continue;
                }
                if ($resource->service !== $permission->service || $resource->kind !== $permission->resource_kind) {
                    continue;
                }
                $desired[] = [
                    'permission_group_id' => $group->id,
                    'permission_id' => $permissionId,
                    'resource_id' => $resourceId,
                ];
            }
        }

        PermissionGroupResource::query()->where('permission_group_id', $group->id)->delete();
        if (!empty($desired)) {
            $now = now();
            $rows = array_map(fn (array $r) => $r + ['created_at' => $now, 'updated_at' => $now], $desired);
            PermissionGroupResource::query()->insert($rows);
        }
    }

    /**
     * @return array<string, array{permission_id:int, resource_id:?int}>
     */
    private function loadGroupPairs(int $groupId): array
    {
        $pairs = [];

        $group = PermissionGroup::query()
            ->with([
                'permissions' => fn ($q) => $q->where('auto_grant', true)->orWhere('auto_revoke', true),
                'permissionResources',
            ])
            ->find($groupId);

        if (!$group) {
            return $pairs;
        }

        $resourcesByPermission = [];
        foreach ($group->permissionResources as $row) {
            $resourcesByPermission[$row->permission_id][] = $row->resource_id;
        }

        foreach ($group->permissions as $permission) {
            $scope = $permission->scope ?? PermissionScope::Service;

            if ($scope === PermissionScope::Resource) {
                foreach ($resourcesByPermission[$permission->id] ?? [] as $resourceId) {
                    $key = UserAutoGrantPairsCollector::key($permission->id, $resourceId);
                    $pairs[$key] = [
                        'permission_id' => $permission->id,
                        'resource_id' => $resourceId,
                        'auto_grant' => (bool) $permission->auto_grant,
                        'auto_revoke' => (bool) $permission->auto_revoke,
                    ];
                }
                continue;
            }

            $key = UserAutoGrantPairsCollector::key($permission->id, null);
            $pairs[$key] = [
                'permission_id' => $permission->id,
                'resource_id' => null,
                'auto_grant' => (bool) $permission->auto_grant,
                'auto_revoke' => (bool) $permission->auto_revoke,
            ];
        }

        return $pairs;
    }

    /**
     * Пользователи, на которых может повлиять изменение состава прав/ресурсов группы:
     * прямые участники группы + участники позиций, использующих группу, + участники
     * дочерних позиций (которые получают права parent через наследование).
     *
     * @return array<int>
     */
    private function affectedUserIds(PermissionGroup $group): array
    {
        $userIds = $group->users()->pluck('virtual_users.id')->all();

        $positionIds = DB::table('position_permission_group')
            ->where('permission_group_id', $group->id)
            ->pluck('position_id')
            ->all();

        $tree = $positionIds;
        foreach ($positionIds as $positionId) {
            $this->collectChildrenIds((int) $positionId, $tree);
        }

        if (!empty($tree)) {
            $positionUsers = DB::table('virtual_user_positions')
                ->whereIn('position_id', $tree)
                ->pluck('virtual_user_id')
                ->all();
            $userIds = array_merge($userIds, $positionUsers);
        }

        return array_values(array_unique(array_map('intval', $userIds)));
    }

    private function collectChildrenIds(int $positionId, array &$ids): void
    {
        $children = DB::table('positions')->where('parent_id', $positionId)->pluck('id');
        foreach ($children as $childId) {
            $childId = (int) $childId;
            if (in_array($childId, $ids, true)) {
                continue;
            }
            $ids[] = $childId;
            $this->collectChildrenIds($childId, $ids);
        }
    }
}
