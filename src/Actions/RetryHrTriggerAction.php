<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Models\HrEventTriggerAssignment;
use ArcheeNic\PermissionRegistry\Models\HrTriggerExecutionLog;
use ArcheeNic\PermissionRegistry\Services\HrEventTriggerExecutor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RetryHrTriggerAction
{
    public function __construct(
        private HrEventTriggerExecutor $executor
    ) {
    }

    /**
     * @param array<string, mixed> $manualGlobalFields
     */
    public function execute(int $executionLogId, array $manualGlobalFields = [], ?int $actorId = null): bool
    {
        $callback = function () use ($executionLogId, $manualGlobalFields, $actorId): bool {
            $logEntry = HrTriggerExecutionLog::query()
                ->lockForUpdate()
                ->find($executionLogId);
            if (! $logEntry) {
                Log::warning('HR retry aborted: execution log not found', [
                    'execution_log_id' => $executionLogId,
                ]);

                return false;
            }

            $failedAssignment = HrEventTriggerAssignment::query()->find($logEntry->hr_event_trigger_assignment_id);
            if (! $failedAssignment) {
                Log::warning('HR retry aborted: failed assignment not found', [
                    'execution_log_id' => $executionLogId,
                    'assignment_id' => $logEntry->hr_event_trigger_assignment_id,
                ]);

                return false;
            }

            $assignments = HrEventTriggerAssignment::query()
                ->where('event_type', $logEntry->event_type)
                ->where('employee_category', $logEntry->employee_category)
                ->where('is_enabled', true)
                ->orderBy('order')
                ->get();
            $startIndex = $assignments->search(fn (HrEventTriggerAssignment $assignment): bool => $assignment->id === $failedAssignment->id);
            if ($startIndex === false) {
                Log::warning('HR retry aborted: assignment missing in current chain', [
                    'execution_log_id' => $executionLogId,
                    'assignment_id' => $failedAssignment->id,
                ]);

                return false;
            }

            if ($actorId !== null) {
                $context = (array) ($logEntry->resolution_context ?? []);
                $context['retry_requested_by'] = $actorId;
                $context['retry_requested_at'] = now()->toIso8601String();
                $logEntry->update([
                    HrTriggerExecutionLog::ACTOR_ID => $actorId,
                    HrTriggerExecutionLog::RESOLUTION_CONTEXT => $context,
                ]);
            }

            $result = $this->executor->executeChainFromIndex(
                $logEntry->virtual_user_id,
                $logEntry->event_type,
                $startIndex,
                $manualGlobalFields
            );
            if ($result) {
                $logEntry->update([
                    HrTriggerExecutionLog::STATUS => 'success',
                    HrTriggerExecutionLog::ERROR_MESSAGE => null,
                    HrTriggerExecutionLog::COMPLETED_AT => now(),
                ]);
            }

            return $result;
        };

        if (DB::getDriverName() === 'sqlite') {
            return $callback();
        }

        return DB::transaction($callback);
    }
}
