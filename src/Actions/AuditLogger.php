<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Models\PermissionAuditLog;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    private const STRUCTURED_KEYS = [
        'virtual_user_id',
        'permission_id',
        'field_values',
        'meta',
    ];

    public function log(string $action, ?int $userId, array $data = []): void
    {
        $request = request();
        $ip = $request?->ip();
        $userAgent = $request?->userAgent();
        $now = now();

        Log::channel('permission-registry')->info($action, array_merge([
            'user_id' => $userId,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'timestamp' => $now->toIso8601String(),
        ], $data));

        $context = array_diff_key($data, array_flip(self::STRUCTURED_KEYS));

        PermissionAuditLog::create([
            PermissionAuditLog::ACTION => $action,
            PermissionAuditLog::ACTOR_ID => $userId,
            PermissionAuditLog::VIRTUAL_USER_ID => $data['virtual_user_id'] ?? null,
            PermissionAuditLog::PERMISSION_ID => $data['permission_id'] ?? null,
            PermissionAuditLog::IP_ADDRESS => $ip,
            PermissionAuditLog::USER_AGENT => $userAgent,
            PermissionAuditLog::FIELD_VALUES => $data['field_values'] ?? null,
            PermissionAuditLog::META => $data['meta'] ?? null,
            PermissionAuditLog::CONTEXT => $context ?: null,
            PermissionAuditLog::CREATED_AT => $now,
        ]);
    }
}
