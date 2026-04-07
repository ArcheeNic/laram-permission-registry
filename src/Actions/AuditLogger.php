<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use Illuminate\Support\Facades\Log;

class AuditLogger
{
    public function log(string $action, ?int $userId, array $data = []): void
    {
        $request = request();

        Log::channel('permission-registry')->info($action, array_merge([
            'user_id' => $userId,
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ], $data));
    }
}
