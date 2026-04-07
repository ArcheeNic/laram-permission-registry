<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\ProcessFireRevocationsAction;
use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use ArcheeNic\PermissionRegistry\Enums\ManagementMode;
use ArcheeNic\PermissionRegistry\Enums\ManualTaskStatus;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Jobs\RevokeMultiplePermissionsJob;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\ManualProvisionTask;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Facades\Queue;

class ProcessFireRevocationsActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_it_revokes_remaining_automated_permissions_and_creates_manual_tasks(): void
    {
        $user = VirtualUser::create([
            'name' => 'Fired User',
            'status' => VirtualUserStatus::DEACTIVATED,
            'employee_category' => EmployeeCategory::STAFF,
        ]);

        $automatedPermission = Permission::create([
            'service' => 'test',
            'name' => 'automated-remaining',
            'management_mode' => ManagementMode::AUTOMATED->value,
        ]);
        $manualPermission = Permission::create([
            'service' => 'test',
            'name' => 'manual-remaining',
            'management_mode' => ManagementMode::MANUAL->value,
        ]);
        $declarativePermission = Permission::create([
            'service' => 'test',
            'name' => 'declarative-remaining',
            'management_mode' => ManagementMode::DECLARATIVE->value,
        ]);

        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $automatedPermission->id,
            'enabled' => true,
            'meta' => ['auto_granted' => false],
            'status' => 'granted',
            'granted_at' => now(),
        ]);
        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $manualPermission->id,
            'enabled' => true,
            'meta' => ['auto_granted' => false],
            'status' => 'manual_pending',
            'granted_at' => now(),
        ]);
        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $declarativePermission->id,
            'enabled' => true,
            'meta' => ['auto_granted' => false],
            'status' => 'declared',
            'granted_at' => now(),
        ]);

        app(ProcessFireRevocationsAction::class)->handle($user->id);

        Queue::assertPushed(RevokeMultiplePermissionsJob::class, function (RevokeMultiplePermissionsJob $job) use ($automatedPermission) {
            $permissionIds = $this->readPrivateProperty($job, 'permissionIds');

            return $permissionIds === [$automatedPermission->id];
        });

        $this->assertDatabaseHas('manual_provision_tasks', [
            ManualProvisionTask::GRANTED_PERMISSION_ID => GrantedPermission::query()
                ->where('virtual_user_id', $user->id)
                ->where('permission_id', $manualPermission->id)
                ->value('id'),
            ManualProvisionTask::STATUS => ManualTaskStatus::PENDING->value,
        ]);
        $this->assertDatabaseHas('manual_provision_tasks', [
            ManualProvisionTask::GRANTED_PERMISSION_ID => GrantedPermission::query()
                ->where('virtual_user_id', $user->id)
                ->where('permission_id', $declarativePermission->id)
                ->value('id'),
            ManualProvisionTask::STATUS => ManualTaskStatus::PENDING->value,
        ]);
    }

    public function test_it_is_idempotent_for_manual_revocation_tasks(): void
    {
        $user = VirtualUser::create([
            'name' => 'Fired User',
            'status' => VirtualUserStatus::DEACTIVATED,
            'employee_category' => EmployeeCategory::STAFF,
        ]);

        $manualPermission = Permission::create([
            'service' => 'test',
            'name' => 'manual-remaining',
            'management_mode' => ManagementMode::MANUAL->value,
        ]);

        $grantedPermission = GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $manualPermission->id,
            'enabled' => true,
            'meta' => ['auto_granted' => false],
            'status' => 'manual_pending',
            'granted_at' => now(),
        ]);

        $action = app(ProcessFireRevocationsAction::class);
        $action->handle($user->id);
        $action->handle($user->id);

        $this->assertSame(1, ManualProvisionTask::query()
            ->where(ManualProvisionTask::GRANTED_PERMISSION_ID, $grantedPermission->id)
            ->count());
    }

    private function readPrivateProperty(object $instance, string $property): mixed
    {
        $reflection = new \ReflectionClass($instance);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);

        return $prop->getValue($instance);
    }
}

