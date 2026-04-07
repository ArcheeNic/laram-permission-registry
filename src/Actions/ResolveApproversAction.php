<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\ApproverType;
use ArcheeNic\PermissionRegistry\Models\ApprovalPolicy;
use ArcheeNic\PermissionRegistry\Models\VirtualUserPosition;
use Illuminate\Support\Collection;

class ResolveApproversAction
{
    /**
     * @return Collection<int, int> virtual_user_ids who can approve
     */
    public function handle(ApprovalPolicy $policy): Collection
    {
        $approverIds = collect();

        foreach ($policy->approvers as $approver) {
            match ($approver->approver_type) {
                ApproverType::VIRTUAL_USER => $approverIds->push($approver->approver_id),
                ApproverType::POSITION => $approverIds = $approverIds->merge(
                    VirtualUserPosition::where('position_id', $approver->approver_id)
                        ->pluck('virtual_user_id')
                ),
            };
        }

        return $approverIds->unique()->values();
    }
}
