<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Middleware;

use ArcheeNic\PermissionRegistry\Actions\PermissionChecker;
use ArcheeNic\PermissionRegistry\Contracts\UserToVirtualUserResolver;
use ArcheeNic\PermissionRegistry\Middleware\CheckPermission;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CheckPermissionTest extends TestCase
{
    private function makeMiddleware(
        ?UserToVirtualUserResolver $resolver = null,
        ?PermissionChecker $checker = null
    ): CheckPermission {
        return new CheckPermission(
            $resolver ?? Mockery::mock(UserToVirtualUserResolver::class),
            $checker ?? Mockery::mock(PermissionChecker::class),
        );
    }

    public function test_aborts_401_when_not_authenticated(): void
    {
        Auth::shouldReceive('id')->andReturn(null);

        $middleware = $this->makeMiddleware();
        $request = Request::create('/test', 'GET');

        $this->expectException(HttpException::class);

        $middleware->handle($request, fn () => new Response(), 'service', 'perm');
    }

    public function test_aborts_403_when_virtual_user_not_found(): void
    {
        Auth::shouldReceive('id')->andReturn(1);

        $resolver = Mockery::mock(UserToVirtualUserResolver::class);
        $resolver->shouldReceive('resolve')->with(1)->andReturn(null);

        $middleware = $this->makeMiddleware($resolver);
        $request = Request::create('/test', 'GET');

        $this->expectException(HttpException::class);

        $middleware->handle($request, fn () => new Response(), 'service', 'perm');
    }

    public function test_aborts_403_when_no_permission(): void
    {
        Auth::shouldReceive('id')->andReturn(1);

        $resolver = Mockery::mock(UserToVirtualUserResolver::class);
        $resolver->shouldReceive('resolve')->with(1)->andReturn(10);

        $checker = Mockery::mock(PermissionChecker::class);
        $checker->shouldReceive('hasPermission')->with(10, 'service', 'perm')->andReturn(false);

        $middleware = $this->makeMiddleware($resolver, $checker);
        $request = Request::create('/test', 'GET');

        $this->expectException(HttpException::class);

        $middleware->handle($request, fn () => new Response(), 'service', 'perm');
    }

    public function test_passes_when_has_permission(): void
    {
        Auth::shouldReceive('id')->andReturn(1);

        $resolver = Mockery::mock(UserToVirtualUserResolver::class);
        $resolver->shouldReceive('resolve')->with(1)->andReturn(10);

        $checker = Mockery::mock(PermissionChecker::class);
        $checker->shouldReceive('hasPermission')->with(10, 'bitrix24', 'invite_user')->andReturn(true);

        $middleware = $this->makeMiddleware($resolver, $checker);
        $request = Request::create('/test', 'GET');
        $expected = new Response('ok', 200);

        $result = $middleware->handle($request, fn () => $expected, 'bitrix24', 'invite_user');

        $this->assertSame($expected, $result);
    }
}
