<?php

namespace ArcheeNic\PermissionRegistry\Services;

use ArcheeNic\PermissionRegistry\DataTransferObjects\HrTriggerExecutionResult;
use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use ArcheeNic\PermissionRegistry\Enums\HrTriggerExecutionStatus;
use ArcheeNic\PermissionRegistry\Contracts\PermissionTriggerInterface;
use ArcheeNic\PermissionRegistry\Models\HrEventTriggerAssignment;
use ArcheeNic\PermissionRegistry\Models\HrTriggerExecutionLog;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\ValueObjects\TriggerContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class HrEventTriggerExecutor
{
    /** @var array<string, bool>|null */
    private ?array $allowedTriggerClasses = null;

    private ?HrTriggerExecutionResult $lastResult = null;

    public function __construct(
        private TriggerFieldMappingService $mappingService,
        private TriggerDiscoveryService $triggerDiscoveryService
    ) {
    }

    public function execute(int $virtualUserId, string $eventType): bool
    {
        return $this->executeChainFromIndex($virtualUserId, $eventType);
    }

    public function getLastResult(): ?HrTriggerExecutionResult
    {
        return $this->lastResult;
    }

    /**
     * @param array<string, mixed> $manualGlobalFields
     */
    public function executeChainFromIndex(
        int $virtualUserId,
        string $eventType,
        int $startIndex = 0,
        array $manualGlobalFields = []
    ): bool
    {
        $this->lastResult = null;

        $virtualUser = VirtualUser::query()->find($virtualUserId);
        if (! $virtualUser) {
            Log::error('HR trigger execution aborted: virtual user not found', [
                'event_type' => $eventType,
                'virtual_user_id' => $virtualUserId,
            ]);

            $this->lastResult = new HrTriggerExecutionResult(
                success: false,
                errorMessage: 'Virtual user not found',
            );

            return false;
        }

        $rawCategory = $virtualUser->getRawOriginal('employee_category');
        $category = $this->resolveCategory($rawCategory);
        if (! $category) {
            Log::error('HR trigger execution aborted: employee category is missing or invalid', [
                'event_type' => $eventType,
                'virtual_user_id' => $virtualUserId,
                'employee_category' => $rawCategory,
            ]);

            $this->lastResult = new HrTriggerExecutionResult(
                success: false,
                errorMessage: 'Employee category is missing or invalid',
            );

            return false;
        }

        $assignments = $this->loadAssignments($eventType, $category);

        if ($assignments->isEmpty()) {
            $this->lastResult = HrTriggerExecutionResult::ok();

            return true;
        }

        if ($startIndex < 0 || $startIndex >= $assignments->count()) {
            $this->lastResult = new HrTriggerExecutionResult(
                success: false,
                errorMessage: 'Invalid start index for HR trigger chain',
            );

            return false;
        }

        for ($index = $startIndex; $index < $assignments->count(); $index++) {
            $assignment = $assignments[$index];
            $logEntry = HrTriggerExecutionLog::query()->create([
                HrTriggerExecutionLog::VIRTUAL_USER_ID => $virtualUserId,
                HrTriggerExecutionLog::HR_EVENT_TRIGGER_ASSIGNMENT_ID => $assignment->id,
                HrTriggerExecutionLog::PERMISSION_TRIGGER_ID => $assignment->permission_trigger_id,
                HrTriggerExecutionLog::EVENT_TYPE => $eventType,
                HrTriggerExecutionLog::EMPLOYEE_CATEGORY => $category->value,
                HrTriggerExecutionLog::STATUS => HrTriggerExecutionStatus::RUNNING->value,
                HrTriggerExecutionLog::STARTED_AT => now(),
            ]);

            try {
                $trigger = $this->instantiateTrigger($assignment->trigger->class_name);
                $mapping = $this->mappingService->getMapping($assignment->permission_trigger_id);
                $globalFields = $this->mappingService->applyMapping($virtualUserId, $mapping);
                if ($index === $startIndex && $manualGlobalFields !== []) {
                    $globalFields = array_merge($globalFields, $manualGlobalFields);
                }

                $context = new TriggerContext(
                    virtualUserId: $virtualUserId,
                    permission: new Permission([
                        'service' => 'hr-event',
                        'name' => "hr-{$eventType}",
                    ]),
                    permissionTriggerId: $assignment->permission_trigger_id,
                    fieldValues: [],
                    globalFields: $globalFields,
                    grantedPermission: null,
                    config: $assignment->config ?? []
                );

                $result = $trigger->execute($context);
                if (! $result->success) {
                    $status = $result->awaitingResolution
                        ? HrTriggerExecutionStatus::AWAITING_RESOLUTION->value
                        : HrTriggerExecutionStatus::FAILED->value;
                    $meta = $result->meta;
                    if ($result->awaitingResolution) {
                        $meta['awaiting_resolution'] = true;
                    }
                    $logEntry->update([
                        HrTriggerExecutionLog::STATUS => $status,
                        HrTriggerExecutionLog::COMPLETED_AT => now(),
                        HrTriggerExecutionLog::ERROR_MESSAGE => $result->errorMessage,
                        HrTriggerExecutionLog::META => $meta,
                        HrTriggerExecutionLog::RESOLUTION_CONTEXT => $result->awaitingResolution
                            ? $this->buildResolutionContext($assignment, $result->meta)
                            : null,
                    ]);

                    Log::warning('HR trigger execution failed', [
                        'event_type' => $eventType,
                        'virtual_user_id' => $virtualUserId,
                        'permission_trigger_id' => $assignment->permission_trigger_id,
                        'error' => $result->errorMessage,
                    ]);

                    $this->lastResult = HrTriggerExecutionResult::failed(
                        logId: $logEntry->id,
                        errorMessage: $result->errorMessage,
                        triggerName: $assignment->trigger?->name,
                        permissionTriggerId: $assignment->permission_trigger_id,
                        awaitingResolution: (bool) $result->awaitingResolution,
                    );

                    return false;
                }

                $logEntry->update([
                    HrTriggerExecutionLog::STATUS => HrTriggerExecutionStatus::SUCCESS->value,
                    HrTriggerExecutionLog::COMPLETED_AT => now(),
                    HrTriggerExecutionLog::META => $result->meta,
                ]);
            } catch (\Throwable $e) {
                $logEntry->update([
                    HrTriggerExecutionLog::STATUS => HrTriggerExecutionStatus::FAILED->value,
                    HrTriggerExecutionLog::COMPLETED_AT => now(),
                    HrTriggerExecutionLog::ERROR_MESSAGE => $e->getMessage(),
                    HrTriggerExecutionLog::META => [
                        'exception' => get_class($e),
                    ],
                ]);
                Log::error('HR trigger execution crashed', [
                    'event_type' => $eventType,
                    'virtual_user_id' => $virtualUserId,
                    'permission_trigger_id' => $assignment->permission_trigger_id,
                    'error' => $e->getMessage(),
                ]);

                $this->lastResult = HrTriggerExecutionResult::crashed(
                    logId: $logEntry->id,
                    errorMessage: $e->getMessage(),
                    triggerName: $assignment->trigger?->name,
                    permissionTriggerId: $assignment->permission_trigger_id,
                );

                return false;
            }
        }

        $this->lastResult = HrTriggerExecutionResult::ok();

        return true;
    }

    /**
     * @return Collection<int, HrEventTriggerAssignment>
     */
    private function loadAssignments(string $eventType, EmployeeCategory $category): Collection
    {
        return HrEventTriggerAssignment::query()
            ->where('event_type', $eventType)
            ->forCategory($category)
            ->where('is_enabled', true)
            ->with('trigger')
            ->orderBy('order')
            ->get();
    }

    private function instantiateTrigger(string $className): PermissionTriggerInterface
    {
        if (! $this->isAllowedTriggerClass($className)) {
            throw new \RuntimeException("Trigger class is not whitelisted: {$className}");
        }

        if (!class_exists($className)) {
            throw new \RuntimeException("Trigger class not found: {$className}");
        }

        $instance = app($className);

        if (! $instance instanceof PermissionTriggerInterface) {
            throw new \RuntimeException("Trigger must implement PermissionTriggerInterface: {$className}");
        }

        return $instance;
    }

    private function resolveCategory(mixed $rawCategory): ?EmployeeCategory
    {
        if ($rawCategory instanceof EmployeeCategory) {
            return $rawCategory;
        }

        if (!is_string($rawCategory)) {
            return null;
        }

        return EmployeeCategory::tryFrom($rawCategory);
    }

    private function isAllowedTriggerClass(string $className): bool
    {
        if ($this->allowedTriggerClasses === null) {
            $discovered = $this->triggerDiscoveryService->discover();
            $this->allowedTriggerClasses = [];
            foreach ($discovered as $item) {
                if (! empty($item['class_name']) && is_string($item['class_name'])) {
                    $this->allowedTriggerClasses[$item['class_name']] = true;
                }
            }
        }

        return isset($this->allowedTriggerClasses[$className]);
    }

    private function buildResolutionContext(HrEventTriggerAssignment $assignment, array $meta): array
    {
        return [
            'event_type' => $assignment->event_type,
            'employee_category' => $assignment->employee_category?->value ?? $assignment->employee_category,
            'permission_trigger_id' => $assignment->permission_trigger_id,
            'trigger_config' => $assignment->config ?? [],
            'meta' => $meta,
        ];
    }
}
