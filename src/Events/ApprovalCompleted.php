<?php

namespace ArcheeNic\PermissionRegistry\Events;

use ArcheeNic\PermissionRegistry\Enums\ApprovalRequestStatus;
use ArcheeNic\PermissionRegistry\Models\ApprovalRequest;

readonly class ApprovalCompleted
{
    public function __construct(
        public ApprovalRequest $approvalRequest,
        public ApprovalRequestStatus $result,
    ) {
    }
}
