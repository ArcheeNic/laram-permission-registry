<?php

namespace ArcheeNic\PermissionRegistry\Controllers;

use App\Http\Controllers\Controller;
use ArcheeNic\PermissionRegistry\Actions\GrantPermissionAction;
use ArcheeNic\PermissionRegistry\Actions\RevokePermissionAction;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
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
        $grantedPermissions = GrantedPermission::where('user_id', $user->id)
            ->with(['permission', 'fieldValues.field'])
            ->get();

        // Получаем все доступные права, исключая уже выданные
        $grantedPermissionIds = $grantedPermissions->pluck('permission_id');
        $availablePermissions = Permission::whereNotIn('id', $grantedPermissionIds)->get();

        // Получаем все права с полями для JS
        $permissionsWithFields = Permission::with('fields')->get();

        return view('permission-registry::users.permissions', compact('user', 'grantedPermissions', 'availablePermissions', 'permissionsWithFields'));
    }

    public function grantPermission(Request $request, VirtualUser $user)
    {
        $validator = Validator::make($request->all(), [
            'permission_id' => 'required|exists:permissions,id',
            'expires_at' => 'nullable|date',
            'fields' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $action = app(GrantPermissionAction::class);
        $action->handle(
            $user->id,
            $request->permission_id,
            $request->fields ?? [],
            [], // meta
            $request->expires_at
        );

        return redirect()->route('permission-registry::users.permissions', $user)
            ->with('success', __('permission-registry::Право успешно выдано'));
    }

    public function revokePermission(VirtualUser $user, GrantedPermission $permission)
    {
        $action = app(RevokePermissionAction::class);
        $action->handle($user->id, $permission->permission_id);

        return redirect()->route('permission-registry::users.permissions', $user)
            ->with('success', __('permission-registry::Право успешно отозвано'));
    }
}
