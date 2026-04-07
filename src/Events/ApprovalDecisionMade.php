<?php

namespace ArcheeNic\PermissionRegistry\Events;

use ArcheeNic\PermissionRegistry\Models\ApprovalDecision;
use ArcheeNic\PermissionRegistry\Models\ApprovalRequest;

readonly class ApprovalDecisionMade
{
    public function __construct(
        public ApprovalRequest $approvalRequest,
        public ApprovalDecision $decision,
    ) {
    }
}
