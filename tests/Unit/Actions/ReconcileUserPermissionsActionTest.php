<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\ReconcileUserPermissionsAction;
use ArcheeNic\PermissionRegistry\Jobs\GrantMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Jobs\RevokeMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Facades\Queue;

class ReconcileUserPermissionsActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_dispatches_diff_jobs_and_ignores_manual_permissions(): void
    {
        $user = VirtualUser::create(['name' => 'Recon User', 'status' => VirtualUserStatus::ACTIVE]);

        $keepPermission = Permission::create(['service' => 'test', 'name' => 'keep', 'auto_grant' => true]);
        $newPermission = Permission::create(['service' => 'test', 'name' => 'new', 'auto_grant' => true]);
        $revokedPermission = Permission::create(['service' => 'test', 'name' => 'revoke', 'auto_grant' => true]);
        $manualPermission = Permission::create(['service' => 'test', 'name' => 'manual', 'auto_grant' => true]);

        $position = Position::create(['name' => 'Engineer']);
        $position->permissions()->attach([$keepPermission->id, $newPermission->id]);
        $user->positions()->attach($position->id);

        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $keepPermission->id,
            'enabled' => true,
            'meta' => ['auto_granted' => true],
            'status' => 'granted',
            'granted_at' => now(),
        ]);
        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $revokedPermission->id,
            'enabled' => true,
            'meta' => ['auto_granted' => true],
            'status' => 'granted',
            'granted_at' => now(),
        ]);
        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $manualPermission->id,
            'enabled' => true,
            'meta' => ['auto_granted' => false],
            'status' => 'granted',
            'granted_at' => now(),
        ]);

        app(ReconcileUserPermissionsAction::class)->handle($user->id);

        Queue::assertPushed(GrantMultiplePermissionsJob::class, function (GrantMultiplePermissionsJob $job) use ($newPermission) {
            $permissionsData = $this->readPrivateProperty($job, 'permissionsData');
            $permissionIds = array_column($permissionsData, 'permissionId');

            return $permissionIds === [$newPermission->id];
        });

        Queue::assertPushed(RevokeMultiplePermissionsJob::class, function (RevokeMultiplePermissionsJob $job) use ($revokedPermission, $manualPermission) {
            $permissionIds = $this->readPrivateProperty($job, 'permissionIds');

            return in_array($revokedPermission->id, $permissionIds, true)
                && !in_array($manualPermission->id, $permissionIds, true);
        });
    }

    public function test_collects_permissions_from_position_hierarchy_and_groups(): void
    {
        $user = VirtualUser::create(['name' => 'Hierarchy User', 'status' => VirtualUserStatus::ACTIVE]);

        $parentPermission = Permission::create(['service' => 'test', 'name' => 'parent', 'auto_grant' => true]);
        $childPermission = Permission::create(['service' => 'test', 'name' => 'child', 'auto_grant' => true]);
        $groupPermission = Permission::create(['service' => 'test', 'name' => 'group', 'auto_grant' => true]);

        $parentPosition = Position::create(['name' => 'Parent']);
        $parentPosition->permissions()->attach($parentPermission->id);

        $childPosition = Position::create(['name' => 'Child', 'parent_id' => $parentPosition->id]);
        $childPosition->permissions()->attach($childPermission->id);

        $permissionGroup = PermissionGroup::create(['name' => 'Group']);
        $permissionGroup->permissions()->attach($groupPermission->id);

        $user->positions()->attach($childPosition->id);
        $user->groups()->attach($permissionGroup->id);

        app(ReconcileUserPermissionsAction::class)->handle($user->id);

        Queue::assertPushed(GrantMultiplePermissionsJob::class, function (GrantMultiplePermissionsJob $job) use ($parentPermission, $childPermission, $groupPermission) {
            $permissionsData = $this->readPrivateProperty($job, 'permissionsData');
            $permissionIds = array_column($permissionsData, 'permissionId');

            return in_array($parentPermission->id, $permissionIds, true)
                && in_array($childPermission->id, $permissionIds, true)
                && in_array($groupPermission->id, $permissionIds, true);
        });
    }

    private function readPrivateProperty(object $instance, string $property): mixed
    {
        $reflection = new \ReflectionClass($instance);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);

        return $prop->getValue($instance);
    }
}
