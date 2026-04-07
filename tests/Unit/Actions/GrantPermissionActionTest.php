<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\GrantPermissionAction;
use ArcheeNic\PermissionRegistry\Enums\GrantedPermissionStatus;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Exceptions\UserDeactivatedException;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Services\PermissionDependencyResolver;
use ArcheeNic\PermissionRegistry\Services\PermissionTriggerExecutor;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use ArcheeNic\PermissionRegistry\ValueObjects\DependencyValidationResult;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Mockery;

class GrantPermissionActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Queue::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_creates_granted_permission_with_skip_triggers_true(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $permission = Permission::create(['service' => 'test', 'name' => 'test-perm']);

        $mockResolver = Mockery::mock(PermissionDependencyResolver::class);
        $mockResolver->shouldReceive('validatePermissionDependencies')
            ->andReturn(DependencyValidationResult::valid());
        $this->app->instance(PermissionDependencyResolver::class, $mockResolver);

        $mockExecutor = Mockery::mock(PermissionTriggerExecutor::class);
        $mockExecutor->shouldNotReceive('executeChain');
        $this->app->instance(PermissionTriggerExecutor::class, $mockExecutor);

        $action = app(GrantPermissionAction::class);
        $result = $action->handle($user->id, $permission->id, [], [], null, true);

        $this->assertDatabaseHas('granted_permissions', [
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
            'status' => 'granted',
        ]);
        $this->assertSame('granted', $result->status);
    }

    public function test_throws_validation_exception_when_dependencies_fail(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $permission = Permission::create(['service' => 'test', 'name' => 'test-perm']);

        $mockResolver = Mockery::mock(PermissionDependencyResolver::class);
        $mockResolver->shouldReceive('validatePermissionDependencies')
            ->andReturn(DependencyValidationResult::invalid(
                [['id' => 1, 'name' => 'required-perm']],
                []
            ));
        $this->app->instance(PermissionDependencyResolver::class, $mockResolver);

        $mockExecutor = Mockery::mock(PermissionTriggerExecutor::class);
        $mockExecutor->shouldNotReceive('executeChain');
        $this->app->instance(PermissionTriggerExecutor::class, $mockExecutor);

        $action = app(GrantPermissionAction::class);

        $this->expectException(ValidationException::class);
        $action->handle($user->id, $permission->id, [], [], null, true);
    }

    public function test_sets_status_to_granted_when_skip_triggers_true(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $permission = Permission::create(['service' => 'test', 'name' => 'test-perm']);

        $mockResolver = Mockery::mock(PermissionDependencyResolver::class);
        $mockResolver->shouldReceive('validatePermissionDependencies')
            ->andReturn(DependencyValidationResult::valid());
        $this->app->instance(PermissionDependencyResolver::class, $mockResolver);

        $mockExecutor = Mockery::mock(PermissionTriggerExecutor::class);
        $this->app->instance(PermissionTriggerExecutor::class, $mockExecutor);

        $action = app(GrantPermissionAction::class);
        $result = $action->handle($user->id, $permission->id, [], [], null, true);

        $this->assertEquals(GrantedPermissionStatus::GRANTED->value, $result->status);
    }

    public function test_sets_status_to_pending_when_skip_triggers_false(): void
    {
        $user = VirtualUser::create(['name' => 'Test User', 'status' => VirtualUserStatus::ACTIVE]);
        $permission = Permission::create(['service' => 'test', 'name' => 'test-perm']);

        $mockResolver = Mockery::mock(PermissionDependencyResolver::class);
        $mockResolver->shouldReceive('validatePermissionDependencies')
            ->andReturn(DependencyValidationResult::valid());
        $this->app->instance(PermissionDependencyResolver::class, $mockResolver);

        $mockExecutor = Mockery::mock(PermissionTriggerExecutor::class);
        $mockExecutor->shouldNotReceive('executeChain');
        $this->app->instance(PermissionTriggerExecutor::class, $mockExecutor);

        $action = app(GrantPermissionAction::class);
        $result = $action->handle($user->id, $permission->id, [], [], null, false, false);

        $this->assertEquals(GrantedPermissionStatus::PENDING->value, $result->status);
    }

    public function test_throws_exception_when_user_is_deactivated(): void
    {
        $user = VirtualUser::create([
            'name' => 'Deactivated User',
            'status' => VirtualUserStatus::DEACTIVATED,
        ]);
        $permission = Permission::create(['service' => 'test', 'name' => 'test-perm']);

        $action = app(GrantPermissionAction::class);

        $this->expectException(UserDeactivatedException::class);
        $action->handle($user->id, $permission->id, [], [], null, true);
    }

    public function test_does_not_create_granted_permission_for_deactivated_user(): void
    {
        $user = VirtualUser::create([
            'name' => 'Deactivated User',
            'status' => VirtualUserStatus::DEACTIVATED,
        ]);
        $permission = Permission::create(['service' => 'test', 'name' => 'test-perm']);

        $action = app(GrantPermissionAction::class);

        try {
            $action->handle($user->id, $permission->id, [], [], null, true);
        } catch (UserDeactivatedException) {
        }

        $this->assertDatabaseMissing('granted_permissions', [
            'virtual_user_id' => $user->id,
            'permission_id' => $permission->id,
        ]);
    }
}
