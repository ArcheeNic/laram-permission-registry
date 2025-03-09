<?php

namespace ArcheeNic\PermissionRegistry\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Заглушка middleware для обратной совместимости
 * Не выполняет реальную проверку прав, а просто пропускает запрос
 */
class CheckPermission
{
    /**
     * Обрабатывает запрос без проверки прав
     */
    public function handle(Request $request, Closure $next, string $service, string $permission): Response
    {
        // Не выполняем проверку прав, просто пропускаем запрос
        return $next($request);
    }
}
