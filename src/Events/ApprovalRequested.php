<?php

namespace ArcheeNic\PermissionRegistry\Events;

use ArcheeNic\PermissionRegistry\Models\ApprovalRequest;

readonly class ApprovalRequested
{
    public function __construct(
        public ApprovalRequest $approvalRequest,
        public array $approverIds,
    ) {
    }
}
