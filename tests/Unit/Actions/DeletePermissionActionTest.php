<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\DeletePermissionAction;
use ArcheeNic\PermissionRegistry\Enums\GrantedPermissionStatus;
use ArcheeNic\PermissionRegistry\Exceptions\PermissionCannotBeDeletedException;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionDependency;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class DeletePermissionActionTest extends TestCase
{
    public function test_soft_deletes_when_no_blocking_grants_or_dependents(): void
    {
        $permission = Permission::factory()->create([
            'service' => 'svc',
            'name' => 'orphan-perm',
        ]);

        app(DeletePermissionAction::class)->handle($permission);

        $this->assertSoftDeleted('permissions', ['id' => $permission->id]);
    }

    public function test_blocks_deletion_when_non_terminal_granted_permission_exists(): void
    {
        $permission = Permission::factory()->create([
            'service' => 'svc',
            'name' => 'blocked-by-grant',
        ]);

        GrantedPermission::factory()->create([
            'virtual_user_id' => VirtualUser::factory(),
            'permission_id' => $permission->id,
            'status' => GrantedPermissionStatus::GRANTED->value,
        ]);

        $thrown = null;
        try {
            app(DeletePermissionAction::class)->handle($permission);
        } catch (\Throwable $e) {
            $thrown = $e;
        }

        $this->assertInstanceOf(PermissionCannotBeDeletedException::class, $thrown);
    }

    public function test_blocks_deletion_when_required_by_permission_dependency(): void
    {
        $required = Permission::factory()->create([
            'service' => 'svc',
            'name' => 'required-target',
        ]);
        $dependent = Permission::factory()->create([
            'service' => 'svc',
            'name' => 'has-dependency',
        ]);

        PermissionDependency::create([
            'permission_id' => $dependent->id,
            'required_permission_id' => $required->id,
            'is_strict' => true,
            'event_type' => 'grant',
        ]);

        $thrown = null;
        try {
            app(DeletePermissionAction::class)->handle($required);
        } catch (\Throwable $e) {
            $thrown = $e;
        }

        $this->assertInstanceOf(PermissionCannotBeDeletedException::class, $thrown);
    }

    #[DataProvider('terminalGrantStatusProvider')]
    public function test_soft_deletes_when_only_terminal_grant_statuses_exist(string $status): void
    {
        $permission = Permission::factory()->create([
            'service' => 'svc',
            'name' => 'terminal-only-'.$status,
        ]);

        GrantedPermission::factory()->create([
            'virtual_user_id' => VirtualUser::factory(),
            'permission_id' => $permission->id,
            'status' => $status,
        ]);

        app(DeletePermissionAction::class)->handle($permission);

        $this->assertSoftDeleted('permissions', ['id' => $permission->id]);
    }

    public static function terminalGrantStatusProvider(): array
    {
        return [
            'revoked' => [GrantedPermissionStatus::REVOKED->value],
            'rejected' => [GrantedPermissionStatus::REJECTED->value],
            'failed' => [GrantedPermissionStatus::FAILED->value],
        ];
    }

    public function test_soft_deletes_when_multiple_grants_are_all_terminal(): void
    {
        $permission = Permission::factory()->create([
            'service' => 'svc',
            'name' => 'all-terminal',
        ]);

        foreach (
            [
                GrantedPermissionStatus::REVOKED->value,
                GrantedPermissionStatus::REJECTED->value,
                GrantedPermissionStatus::FAILED->value,
            ] as $status
        ) {
            GrantedPermission::factory()->create([
                'virtual_user_id' => VirtualUser::factory(),
                'permission_id' => $permission->id,
                'status' => $status,
            ]);
        }

        app(DeletePermissionAction::class)->handle($permission);

        $this->assertSoftDeleted('permissions', ['id' => $permission->id]);
    }
}
