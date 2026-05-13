<?php

namespace ArcheeNic\PermissionRegistry\Controllers;

use App\Http\Controllers\Controller;
use ArcheeNic\PermissionRegistry\Enums\PermissionScope;
use ArcheeNic\PermissionRegistry\Jobs\ApplyMembershipResourceDiffJob;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use ArcheeNic\PermissionRegistry\Models\PermissionResource;
use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Models\PositionPermissionResource;
use ArcheeNic\PermissionRegistry\Services\UserAutoGrantPairsCollector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PositionController extends Controller
{
    public function index()
    {
        return view('permission-registry::positions.index');
    }

    public function show(Position $position)
    {
        $position->load(['permissions', 'groups', 'parent', 'children', 'permissionResources.resource']);
        $resourcesByPermission = $position->permissionResources
            ->groupBy('permission_id')
            ->map(fn ($rows) => $rows->map(fn ($r) => $r->resource)->filter()->values());
        return view('permission-registry::positions.show', compact('position', 'resourcesByPermission'));
    }

    public function create()
    {
        $positions = Position::all();
        $permissions = Permission::all();
        $groups = PermissionGroup::all();
        $resourceCatalog = $this->buildResourceCatalog($permissions);
        $selectedResources = [];
        return view('permission-registry::positions.create', compact('positions', 'permissions', 'groups', 'resourceCatalog', 'selectedResources'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:positions,id',
            'permissions' => 'nullable|array',
            'groups' => 'nullable|array',
            'permission_resources' => 'nullable|array',
            'permission_resources.*' => 'array',
            'permission_resources.*.*' => 'integer|exists:permission_resources,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $position = Position::create([
            'name' => $request->name,
            'description' => $request->description,
            'parent_id' => $request->parent_id,
        ]);

        if ($request->has('permissions')) {
            $position->permissions()->attach($request->permissions);
        }

        if ($request->has('groups')) {
            $position->groups()->attach($request->groups);
        }

        $this->syncPermissionResources($position, $request->input('permission_resources', []));

        return redirect()->route('permission-registry::positions.index')
            ->with('success', __('permission-registry::Position created successfully'));
    }

    public function edit(Position $position)
    {
        $position->load(['permissions', 'groups', 'permissionResources']);
        $positions = Position::where('id', '!=', $position->id)->get();
        $permissions = Permission::all();
        $groups = PermissionGroup::all();
        $resourceCatalog = $this->buildResourceCatalog($permissions);
        $selectedResources = $position->permissionResources
            ->groupBy('permission_id')
            ->map(fn ($rows) => $rows->pluck('resource_id')->all())
            ->toArray();

        return view('permission-registry::positions.edit', compact('position', 'positions', 'permissions', 'groups', 'resourceCatalog', 'selectedResources'));
    }

    public function update(Request $request, Position $position)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:positions,id',
            'permissions' => 'nullable|array',
            'groups' => 'nullable|array',
            'permission_resources' => 'nullable|array',
            'permission_resources.*' => 'array',
            'permission_resources.*.*' => 'integer|exists:permission_resources,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($request->parent_id && $position->id == $request->parent_id) {
            return redirect()->back()->withErrors(['parent_id' => __('permission-registry::Cannot set position as its own parent')]);
        }

        $diff = DB::transaction(function () use ($position, $request): array {
            $position->update([
                'name' => $request->name,
                'description' => $request->description,
                'parent_id' => $request->parent_id,
            ]);

            $oldPairs = $this->loadPositionPairs($position->id);

            if ($request->has('permissions')) {
                $position->permissions()->sync((array) $request->input('permissions', []));
            } else {
                $position->permissions()->detach();
            }

            if ($request->has('groups')) {
                $position->groups()->sync($request->groups);
            } else {
                $position->groups()->detach();
            }

            $this->syncPermissionResources($position, $request->input('permission_resources', []));

            $newPairs = $this->loadPositionPairs($position->id);

            return [
                'added' => array_values(array_diff_key($newPairs, $oldPairs)),
                'removed' => array_values(array_diff_key($oldPairs, $newPairs)),
                'userIds' => $this->affectedUserIds($position),
            ];
        });

        if (!empty($diff['userIds']) && (!empty($diff['added']) || !empty($diff['removed']))) {
            ApplyMembershipResourceDiffJob::dispatch(
                'position',
                $position->id,
                $diff['added'],
                $diff['removed'],
                $diff['userIds'],
            )->afterCommit();
        }

        return redirect()->route('permission-registry::positions.show', $position)
            ->with('success', __('permission-registry::Position updated successfully'));
    }

    public function destroy(Position $position)
    {
        $position->delete();

        return redirect()->route('permission-registry::positions.index')
            ->with('success', __('permission-registry::Position deleted successfully'));
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

    private function syncPermissionResources(Position $position, array $input): void
    {
        $permissions = $position->permissions()
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
                    'position_id' => $position->id,
                    'permission_id' => $permissionId,
                    'resource_id' => $resourceId,
                ];
            }
        }

        PositionPermissionResource::query()->where('position_id', $position->id)->delete();
        if (!empty($desired)) {
            $now = now();
            $rows = array_map(fn (array $r) => $r + ['created_at' => $now, 'updated_at' => $now], $desired);
            PositionPermissionResource::query()->insert($rows);
        }
    }

    /**
     * @return array<string, array{permission_id:int, resource_id:?int, auto_grant:bool, auto_revoke:bool}>
     */
    private function loadPositionPairs(int $positionId): array
    {
        $pairs = [];

        $position = Position::query()
            ->with([
                'permissions' => fn ($q) => $q->where('auto_grant', true)->orWhere('auto_revoke', true),
                'permissionResources',
            ])
            ->find($positionId);

        if (!$position) {
            return $pairs;
        }

        $resourcesByPermission = [];
        foreach ($position->permissionResources as $row) {
            $resourcesByPermission[$row->permission_id][] = $row->resource_id;
        }

        foreach ($position->permissions as $permission) {
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
     * Пользователи, чьи права могут зависеть от этой позиции: участники самой позиции
     * и всех дочерних позиций (которые наследуют по parent).
     *
     * @return array<int>
     */
    private function affectedUserIds(Position $position): array
    {
        $ids = [$position->id];
        $this->collectChildrenIds($position->id, $ids);

        return Position::query()
            ->whereIn('id', $ids)
            ->with('users:id')
            ->get()
            ->pluck('users')
            ->flatten()
            ->pluck('id')
            ->unique()
            ->values()
            ->all();
    }

    private function collectChildrenIds(int $positionId, array &$ids): void
    {
        $children = Position::query()->where('parent_id', $positionId)->pluck('id');
        foreach ($children as $childId) {
            if (in_array($childId, $ids, true)) {
                continue;
            }
            $ids[] = $childId;
            $this->collectChildrenIds($childId, $ids);
        }
    }
}
