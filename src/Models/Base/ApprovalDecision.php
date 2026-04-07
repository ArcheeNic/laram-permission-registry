<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class ApprovalDecision extends Model
{
    public const ID = 'id';
    public const APPROVAL_REQUEST_ID = 'approval_request_id';
    public const APPROVER_ID = 'approver_id';
    public const DECISION = 'decision';
    public const COMMENT = 'comment';
    public const DECIDED_AT = 'decided_at';

    protected $fillable = [
        self::APPROVAL_REQUEST_ID,
        self::APPROVER_ID,
        self::DECISION,
        self::COMMENT,
        self::DECIDED_AT,
    ];
}
