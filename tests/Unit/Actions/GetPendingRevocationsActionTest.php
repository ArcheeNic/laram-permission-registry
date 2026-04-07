<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\GetPendingRevocationsAction;
use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use ArcheeNic\PermissionRegistry\Enums\ManagementMode;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Tests\TestCase;

class GetPendingRevocationsActionTest extends TestCase
{
    public function test_it_returns_only_deactivated_users_with_enabled_permissions(): void
    {
        $targetUser = VirtualUser::create([
            'name' => 'Deactivated With Pending',
            'status' => VirtualUserStatus::DEACTIVATED,
            'employee_category' => EmployeeCategory::STAFF,
        ]);

        $deactivatedWithoutPending = VirtualUser::create([
            'name' => 'Deactivated Without Pending',
            'status' => VirtualUserStatus::DEACTIVATED,
            'employee_category' => EmployeeCategory::STAFF,
        ]);

        $activeUser = VirtualUser::create([
            'name' => 'Active With Permission',
            'status' => VirtualUserStatus::ACTIVE,
            'employee_category' => EmployeeCategory::STAFF,
        ]);

        $permission = Permission::create([
            'service' => 'test',
            'name' => 'pending-revoke',
            'management_mode' => ManagementMode::AUTOMATED->value,
        ]);

        GrantedPermission::create([
            'virtual_user_id' => $targetUser->id,
            'permission_id' => $permission->id,
            'enabled' => true,
            'status' => 'granted',
            'granted_at' => now(),
            'meta' => [],
        ]);

        GrantedPermission::create([
            'virtual_user_id' => $deactivatedWithoutPending->id,
            'permission_id' => $permission->id,
            'enabled' => false,
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

        $result = app(GetPendingRevocationsAction::class)->handle();

        $this->assertSame(1, $result->total());
        $this->assertSame($targetUser->id, $result->items()[0]->id);
    }

    public function test_it_filters_by_employee_category(): void
    {
        $staffUser = VirtualUser::create([
            'name' => 'Staff Pending',
            'status' => VirtualUserStatus::DEACTIVATED,
            'employee_category' => EmployeeCategory::STAFF,
        ]);

        $contractorUser = VirtualUser::create([
            'name' => 'Contractor Pending',
            'status' => VirtualUserStatus::DEACTIVATED,
            'employee_category' => EmployeeCategory::CONTRACTOR,
        ]);

        $permission = Permission::create([
            'service' => 'test',
            'name' => 'pending-revoke',
            'management_mode' => ManagementMode::AUTOMATED->value,
        ]);

        foreach ([$staffUser, $contractorUser] as $user) {
            GrantedPermission::create([
                'virtual_user_id' => $user->id,
                'permission_id' => $permission->id,
                'enabled' => true,
                'status' => 'granted',
                'granted_at' => now(),
                'meta' => [],
            ]);
        }

        $result = app(GetPendingRevocationsAction::class)->handle(
            employeeCategory: EmployeeCategory::STAFF->value
        );

        $this->assertSame(1, $result->total());
        $this->assertSame($staffUser->id, $result->items()[0]->id);
    }

    public function test_it_builds_summary_without_sql_alias_errors(): void
    {
        $user = VirtualUser::create([
            'name' => 'Summary User',
            'status' => VirtualUserStatus::DEACTIVATED,
            'employee_category' => EmployeeCategory::STAFF,
        ]);

        $automatedPermission = Permission::create([
            'service' => 'test',
            'name' => 'summary-automated',
            'management_mode' => ManagementMode::AUTOMATED->value,
        ]);
        $manualPermission = Permission::create([
            'service' => 'test',
            'name' => 'summary-manual',
            'management_mode' => ManagementMode::MANUAL->value,
        ]);

        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $automatedPermission->id,
            'enabled' => true,
            'status' => 'granted',
            'granted_at' => now(),
            'meta' => [],
        ]);
        GrantedPermission::create([
            'virtual_user_id' => $user->id,
            'permission_id' => $manualPermission->id,
            'enabled' => true,
            'status' => 'manual_pending',
            'granted_at' => now(),
            'meta' => [],
        ]);

        $summary = app(GetPendingRevocationsAction::class)->getSummary();

        $this->assertSame(1, $summary['users_count']);
        $this->assertSame(2, $summary['permissions_count']);
        $this->assertSame(1, $summary['automated_count']);
        $this->assertSame(1, $summary['manual_count']);
        $this->assertSame(0, $summary['declarative_count']);
    }
}

