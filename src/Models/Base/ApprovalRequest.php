<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
    public const ID = 'id';
    public const GRANTED_PERMISSION_ID = 'granted_permission_id';
    public const APPROVAL_POLICY_ID = 'approval_policy_id';
    public const STATUS = 'status';
    public const REQUESTED_BY = 'requested_by';
    public const RESOLVED_AT = 'resolved_at';
    public const COMMENT = 'comment';

    protected $fillable = [
        self::GRANTED_PERMISSION_ID,
        self::APPROVAL_POLICY_ID,
        self::STATUS,
        self::REQUESTED_BY,
        self::RESOLVED_AT,
        self::COMMENT,
    ];
}
