<?php

namespace ArcheeNic\PermissionRegistry\Controllers\Api;

use Illuminate\Routing\Controller;
use ArcheeNic\PermissionRegistry\Actions\AssignVirtualUserGroupAction;
use ArcheeNic\PermissionRegistry\Actions\AssignVirtualUserPositionAction;
use ArcheeNic\PermissionRegistry\Actions\CreateVirtualUserAction;
use ArcheeNic\PermissionRegistry\Actions\GrantPermissionAction;
use ArcheeNic\PermissionRegistry\Actions\RevokePermissionAction;
use ArcheeNic\PermissionRegistry\Http\Requests\AssignGroupRequest;
use ArcheeNic\PermissionRegistry\Http\Requests\AssignPositionRequest;
use ArcheeNic\PermissionRegistry\Http\Requests\CreateUserRequest;
use ArcheeNic\PermissionRegistry\Http\Requests\GrantPermissionRequest;
use ArcheeNic\PermissionRegistry\Http\Resources\VirtualUserResource;
use ArcheeNic\PermissionRegistry\Http\Resources\GrantedPermissionResource;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UserApiController extends Controller
{
    /**
     * Создание пользователя
     */
    public function store(CreateUserRequest $request, CreateVirtualUserAction $action): JsonResponse
    {
        $user = $action->handle($request->input('global_fields', []));

        return (new VirtualUserResource($user->load(['fieldValues.field', 'positions', 'groups'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Выдача права пользователю
     */
    public function grantPermission(
        GrantPermissionRequest $request,
        VirtualUser $user,
        GrantPermissionAction $action
    ): JsonResponse {
        $grantedPermission = $action->handle(
            userId: $user->id,
            permissionId: $request->input('permission_id'),
            fieldValues: $request->input('field_values', []),
            meta: $request->input('meta', []),
            expiresAt: $request->input('expires_at'),
            skipTriggers: $request->input('skip_triggers', false),
            executeTriggersSync: $request->input('execute_sync', false)
        );

        return (new GrantedPermissionResource($grantedPermission->load(['permission', 'fieldValues.field'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Отзыв права у пользователя
     */
    public function revokePermission(
        VirtualUser $user,
        GrantedPermission $permission,
        RevokePermissionAction $action
    ): Response {
        $action->handle(
            userId: $user->id,
            permissionId: $permission->permission_id,
            skipTriggers: false,
            executeTriggersSync: false
        );

        return response()->noContent();
    }

    /**
     * Назначение должности пользователю
     */
    public function assignPosition(
        AssignPositionRequest $request,
        VirtualUser $user,
        AssignVirtualUserPositionAction $action
    ): JsonResponse {
        $action->handle($user->id, $request->input('position_id'));

        return response()->json([
            'message' => 'Position assigned successfully',
            'user' => new VirtualUserResource($user->load(['positions', 'groups', 'grantedPermissions']))
        ]);
    }

    /**
     * Отзыв должности у пользователя
     */
    public function revokePosition(
        VirtualUser $user,
        Position $position,
        AssignVirtualUserPositionAction $action
    ): Response {
        $action->remove($user->id, $position->id);

        return response()->noContent();
    }

    /**
     * Назначение группы пользователю
     */
    public function assignGroup(
        AssignGroupRequest $request,
        VirtualUser $user,
        AssignVirtualUserGroupAction $action
    ): JsonResponse {
        $action->handle($user->id, $request->input('group_id'));

        return response()->json([
            'message' => 'Group assigned successfully',
            'user' => new VirtualUserResource($user->load(['positions', 'groups', 'grantedPermissions']))
        ]);
    }

    /**
     * Отзыв группы у пользователя
     */
    public function revokeGroup(
        VirtualUser $user,
        PermissionGroup $group,
        AssignVirtualUserGroupAction $action
    ): Response {
        $action->remove($user->id, $group->id);

        return response()->noContent();
    }

}
