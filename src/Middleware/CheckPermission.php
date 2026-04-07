<?php

namespace ArcheeNic\PermissionRegistry\Middleware;

use ArcheeNic\PermissionRegistry\Actions\PermissionChecker;
use ArcheeNic\PermissionRegistry\Contracts\UserToVirtualUserResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function __construct(
        private UserToVirtualUserResolver $userResolver,
        private PermissionChecker $permissionChecker
    ) {}

    public function handle(Request $request, Closure $next, string $service, string $permission): Response
    {
        $userId = auth()->id();
        if (!$userId) {
            abort(401);
        }

        $virtualUserId = $this->userResolver->resolve($userId);
        if ($virtualUserId === null) {
            abort(403);
        }

        if (!$this->permissionChecker->hasPermission($virtualUserId, $service, $permission)) {
            abort(403);
        }

        return $next($request);
    }
}
