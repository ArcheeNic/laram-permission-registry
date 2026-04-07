<?php

namespace ArcheeNic\PermissionRegistry\Models\Base;

use Illuminate\Database\Eloquent\Model;

class ApprovalPolicyApprover extends Model
{
    public const ID = 'id';
    public const APPROVAL_POLICY_ID = 'approval_policy_id';
    public const APPROVER_TYPE = 'approver_type';
    public const APPROVER_ID = 'approver_id';

    protected $fillable = [
        self::APPROVAL_POLICY_ID,
        self::APPROVER_TYPE,
        self::APPROVER_ID,
    ];
}
