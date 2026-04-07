<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\CopyPermissionAction;
use ArcheeNic\PermissionRegistry\Enums\ApprovalType;
use ArcheeNic\PermissionRegistry\Enums\ApproverType;
use ArcheeNic\PermissionRegistry\Models\ApprovalPolicy;
use ArcheeNic\PermissionRegistry\Models\ApprovalPolicyApprover;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionDependency;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\PermissionTrigger;
use ArcheeNic\PermissionRegistry\Models\PermissionTriggerAssignment;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Tests\TestCase;

class CopyPermissionActionTest extends TestCase
{
    public function test_copies_permission_with_all_related_definitions(): void
    {
        $requiredPermission = Permission::factory()->create([
            'service' => 'crm',
            'name' => 'required-access',
        ]);

        $source = Permission::factory()->create([
            'service' => 'crm',
            'name' => 'manage-users',
            'description' => 'Source description',
            'tags' => ['a', 'b'],
            'auto_grant' => true,
            'auto_revoke' => true,
            'management_mode' => 'manual',
            'risk_level' => 'high',
            'attestation_period_days' => 30,
        ]);

        $owner = VirtualUser::factory()->create();
        $source->update(['system_owner_virtual_user_id' => $owner->id]);

        $field = PermissionField::factory()->create();
        $source->fields()->attach($field->id);

        $trigger = PermissionTrigger::create([
            'name' => 'copy-test-trigger',
            'class_name' => 'Tests\\FakeTrigger',
            'description' => null,
            'type' => 'both',
            'is_active' => true,
            'is_configured' => true,
        ]);

        PermissionTriggerAssignment::create([
            'permission_id' => $source->id,
            'permission_trigger_id' => $trigger->id,
            'event_type' => 'grant',
            'order' => 1,
            'is_enabled' => true,
            'config' => ['foo' => 'bar'],
        ]);

        PermissionDependency::create([
            'permission_id' => $source->id,
            'required_permission_id' => $requiredPermission->id,
            'is_strict' => true,
            'event_type' => 'grant',
        ]);

        $policy = ApprovalPolicy::factory()->create([
            'permission_id' => $source->id,
            'approval_type' => ApprovalType::N_OF_M->value,
            'required_count' => 2,
            'is_active' => true,
        ]);

        $approver = VirtualUser::factory()->create();
        ApprovalPolicyApprover::create([
            'approval_policy_id' => $policy->id,
            'approver_type' => ApproverType::VIRTUAL_USER->value,
            'approver_id' => $approver->id,
        ]);

        $copy = app(CopyPermissionAction::class)->handle($source);

        $this->assertNotSame($source->id, $copy->id);
        $this->assertSame($source->service, $copy->service);
        $this->assertNotSame($source->name, $copy->name);
        $this->assertSame($source->description, $copy->description);
        $this->assertSame($source->tags, $copy->tags);
        $this->assertSame($source->auto_grant, $copy->auto_grant);
        $this->assertSame($source->auto_revoke, $copy->auto_revoke);
        $this->assertSame($source->management_mode->value, $copy->management_mode->value);
        $this->assertSame($source->risk_level->value, $copy->risk_level->value);
        $this->assertSame($source->system_owner_virtual_user_id, $copy->system_owner_virtual_user_id);
        $this->assertSame($source->attestation_period_days, $copy->attestation_period_days);

        $this->assertEqualsCanonicalizing(
            $source->fields->pluck('id')->all(),
            $copy->fields()->pluck('permission_fields.id')->all()
        );

        $this->assertDatabaseHas('permission_trigger_assignments', [
            'permission_id' => $copy->id,
            'permission_trigger_id' => $trigger->id,
            'event_type' => 'grant',
            'order' => 1,
            'is_enabled' => true,
        ]);

        $this->assertDatabaseHas('permission_dependencies', [
            'permission_id' => $copy->id,
            'required_permission_id' => $requiredPermission->id,
            'is_strict' => true,
            'event_type' => 'grant',
        ]);

        $copiedPolicy = $copy->fresh()->approvalPolicy;
        $this->assertNotNull($copiedPolicy);
        $this->assertSame($policy->approval_type->value, $copiedPolicy->approval_type->value);
        $this->assertSame($policy->required_count, $copiedPolicy->required_count);
        $this->assertSame($policy->is_active, $copiedPolicy->is_active);

        $this->assertDatabaseHas('approval_policy_approvers', [
            'approval_policy_id' => $copiedPolicy->id,
            'approver_type' => ApproverType::VIRTUAL_USER->value,
            'approver_id' => $approver->id,
        ]);
    }

    public function test_generates_unique_name_for_copy_with_same_service(): void
    {
        $source = Permission::factory()->create([
            'service' => 'hr',
            'name' => 'view-salary',
        ]);

        $suffix = __('permission-registry::(copy)');

        Permission::factory()->create([
            'service' => 'hr',
            'name' => 'view-salary ' . $suffix,
        ]);

        $copy = app(CopyPermissionAction::class)->handle($source);

        $this->assertSame(
            'view-salary ' . str_replace(')', ' 2)', $suffix),
            $copy->name
        );
    }
}
