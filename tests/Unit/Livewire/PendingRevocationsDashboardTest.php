<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Livewire;

use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use ArcheeNic\PermissionRegistry\Enums\ManagementMode;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Jobs\RevokeMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Livewire\PendingRevocationsDashboard;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\ManualProvisionTask;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;

class PendingRevocationsDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Gate::shouldReceive('authorize')->andReturnTrue();
    }

    public function test_it_renders_only_deactivated_users_with_pending_permissions(): void
    {
        $deactivatedUser = VirtualUser::create([
            'name' => 'Deactivated',
            'status' => VirtualUserStatus::DEACTIVATED,
            'employee_category' => EmployeeCategory::STAFF,
        ]);

        $activeUser = VirtualUser::create([
            'name' => 'Active',
            'status' => VirtualUserStatus::ACTIVE,
            'employee_category' => EmployeeCategory::STAFF,
        ]);

        $permission = Permission::create([
            'service' => 'test',
            'name' => 'pending-revoke',
            'management_mode' => ManagementMode::AUTOMATED->value,
        ]);

        GrantedPermission::create([
            'virtual_user_id' => $deactivatedUser->id,
            'permission_id' => $permission->id,
            'enabled' => true,
            'status' => 'granted',
            'granted_at' => now(),
            'meta' => [],
        ]);

        GrantedPermission::create([
            'virtual_user_id' => $activeUser->id,
            'permission_id' => $permission->id,
            'enabled' => true,
            'status' => 'granted',
            'granted_at' => now(),
            'meta' => [],
        ]);

        $component = app(PendingRevocationsDashboard::class);
        $rows = $component->rows;

        $this->assertSame(1, $rows->total());
        $this->assertSame($deactivatedUser->id, $rows->items()[0]->id);
    }

    public function test_it_dispatches_revoke_job_for_automated_permissions(): void
    {
        $user = VirtualUser::create([
            'name' => 'Deactivated',
            'status' => VirtualUserStatus::DEACTIVATED,
            'employee_category' => EmployeeCategory::STAFF,
        ]);

        $automated = Permission::create([
            'service' => 'test',
            'name' => 'automated',
            'management_mode' => ManagementMode::AUTOMATED->value,
        ]);
        $manual = Permission::create([
            'service' => 'test',
            'name' => 'manual',
            'management_mode' => ManagementMode::MANUAL->value,
        ]);

        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $automated->id,
            'enabled' => true,
            'status' => 'granted',
            'granted_at' => now(),
            'meta' => [],
        ]);
        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $manual->id,
            'enabled' => true,
            'status' => 'manual_pending',
            'granted_at' => now(),
            'meta' => [],
        ]);

        $component = app(PendingRevocationsDashboard::class);
        $component->revokeAutomated($user->id);

        Queue::assertPushed(RevokeMultiplePermissionsJob::class, function (RevokeMultiplePermissionsJob $job) use ($automated) {
            $permissionIds = $this->readPrivateProperty($job, 'permissionIds');

            return $permissionIds === [$automated->id];
        });
    }

    public function test_it_creates_manual_revocation_tasks(): void
    {
        $user = VirtualUser::create([
            'name' => 'Deactivated',
            'status' => VirtualUserStatus::DEACTIVATED,
            'employee_category' => EmployeeCategory::STAFF,
        ]);

        $manual = Permission::create([
            'service' => 'test',
            'name' => 'manual',
            'management_mode' => ManagementMode::MANUAL->value,
        ]);

        $grantedPermission = GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $manual->id,
            'enabled' => true,
            'status' => 'manual_pending',
            'granted_at' => now(),
            'meta' => ['auto_granted' => false],
        ]);

        $component = app(PendingRevocationsDashboard::class);
        $component->createManualTasks($user->id);

        $this->assertDatabaseHas('manual_provision_tasks', [
            ManualProvisionTask::GRANTED_PERMISSION_ID => $grantedPermission->id,
        ]);
    }

    private function readPrivateProperty(object $instance, string $property): mixed
    {
        $reflection = new \ReflectionClass($instance);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);

        return $prop->getValue($instance);
    }
}

