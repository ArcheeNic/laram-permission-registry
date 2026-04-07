<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Enums\HrTriggerExecutionStatus;
use ArcheeNic\PermissionRegistry\Models\HrTriggerExecutionLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResolveEmailConflictAction
{
    public function __construct(
        private RetryHrTriggerAction $retryHrTriggerAction
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int $executionLogId, string $strategy, array $payload = [], ?int $actorId = null): bool
    {
        $callback = function () use ($executionLogId, $strategy, $payload, $actorId): bool {
            $logEntry = HrTriggerExecutionLog::query()
                ->lockForUpdate()
                ->find($executionLogId);
            if (! $logEntry) {
                return false;
            }
            if ($logEntry->status !== HrTriggerExecutionStatus::AWAITING_RESOLUTION->value) {
                Log::warning('Ignoring HR conflict resolve request for non-awaiting log', [
                    'execution_log_id' => $executionLogId,
                    'status' => $logEntry->status,
                ]);

                return false;
            }

            return match ($strategy) {
                'increment' => $this->retryWithEmail($logEntry, (string) data_get($logEntry->meta, 'suggested_email'), $strategy, $payload, $actorId),
                'custom_email' => $this->retryWithEmail($logEntry, (string) ($payload['email'] ?? ''), $strategy, $payload, $actorId),
                'cancel' => $this->cancelConflict($logEntry, $payload, $actorId),
                default => false,
            };
        };

        if (DB::getDriverName() === 'sqlite') {
            return $callback();
        }

        return DB::transaction($callback);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function retryWithEmail(
        HrTriggerExecutionLog $logEntry,
        string $email,
        string $strategy,
        array $payload,
        ?int $actorId
    ): bool {
        $normalizedEmail = mb_strtolower(trim($email));
        if (! filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Invalid email provided for HR conflict resolution', [
                'execution_log_id' => $logEntry->id,
                'strategy' => $strategy,
                'email' => $email,
            ]);

            return false;
        }

        $this->markResolutionAttempt($logEntry, $strategy, $payload, $actorId);

        return $this->retryHrTriggerAction->execute(
            $logEntry->id,
            ['override_email' => $normalizedEmail],
            $actorId
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function cancelConflict(HrTriggerExecutionLog $logEntry, array $payload, ?int $actorId): bool
    {
        $this->markResolutionAttempt($logEntry, 'cancel', $payload, $actorId);
        $logEntry->update([
            HrTriggerExecutionLog::STATUS => HrTriggerExecutionStatus::FAILED->value,
            HrTriggerExecutionLog::COMPLETED_AT => now(),
            HrTriggerExecutionLog::ERROR_MESSAGE => __('Найм остановлен до ручного разрешения конфликта email'),
        ]);

        return true;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function markResolutionAttempt(HrTriggerExecutionLog $logEntry, string $strategy, array $payload, ?int $actorId): void
    {
        $context = (array) ($logEntry->resolution_context ?? []);
        $context['resolution_strategy'] = $strategy;
        $context['payload'] = $payload;
        $context['resolved_at'] = now()->toIso8601String();
        if ($actorId !== null) {
            $context['resolved_by'] = $actorId;
        }

        $logEntry->update([
            HrTriggerExecutionLog::ACTOR_ID => $actorId,
            HrTriggerExecutionLog::RESOLUTION_CONTEXT => $context,
        ]);
    }
}
