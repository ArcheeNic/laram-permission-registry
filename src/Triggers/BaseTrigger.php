<?php

namespace ArcheeNic\PermissionRegistry\Triggers;

use ArcheeNic\PermissionRegistry\Contracts\PermissionTriggerInterface;
use ArcheeNic\PermissionRegistry\ValueObjects\TriggerContext;
use ArcheeNic\PermissionRegistry\ValueObjects\TriggerResult;
use Illuminate\Support\Facades\Log;

abstract class BaseTrigger implements PermissionTriggerInterface
{
    public function execute(TriggerContext $context): TriggerResult
    {
        $validationError = $this->validate($context);
        if ($validationError !== null) {
            $meta = $this->buildValidationFailureMeta($context);

            return TriggerResult::failure($validationError, $meta);
        }

        try {
            return $this->perform($context);
        } catch (\Exception $e) {
            Log::error('BaseTrigger: Ошибка выполнения триггера', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return TriggerResult::failure(
                $e->getMessage(),
                [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
        }
    }

    protected function validate(TriggerContext $context): ?string
    {
        $requiredFields = $this->getRequiredFields();
        $globalFields = $context->globalFields ?? [];

        foreach ($requiredFields as $field) {
            if (($field['is_internal'] ?? false)) {
                continue;
            }

            if (($field['required'] ?? false) && empty($globalFields[$field['name'] ?? ''])) {
                return $this->getValidationErrorMessage($field['name'] ?? '');
            }
        }

        $configFields = $this->getConfigFields();
        $config = $context->config ?? [];

        foreach ($configFields as $field) {
            if (($field['required'] ?? false)) {
                $value = $config[$field['name'] ?? ''] ?? null;
                if ($value === null || $value === '') {
                    return $this->getValidationErrorMessage($field['name'] ?? '');
                }
            }
        }

        return null;
    }

    /**
     * Мета для ответа при ошибке валидации: недостающие и ожидаемые поля для ручного продолжения шага.
     */
    protected function buildValidationFailureMeta(TriggerContext $context): array
    {
        $requiredFields = $this->getRequiredFields();
        $globalFields = $context->globalFields ?? [];
        $configFields = $this->getConfigFields();
        $config = $context->config ?? [];

        $missingFields = [];
        $expectedFields = [];

        foreach ($requiredFields as $field) {
            if ($field['is_internal'] ?? false) {
                continue;
            }
            $name = $field['name'] ?? '';
            $item = [
                'name' => $name,
                'description' => $field['description'] ?? $name,
                'required' => (bool) ($field['required'] ?? false),
            ];
            $expectedFields[] = $item;
            if (($field['required'] ?? false) && empty($globalFields[$name])) {
                $missingFields[] = $item;
            }
        }

        foreach ($configFields as $field) {
            $name = $field['name'] ?? '';
            $item = [
                'name' => $name,
                'description' => $field['description'] ?? $name,
                'required' => (bool) ($field['required'] ?? false),
                'is_config' => true,
            ];
            $expectedFields[] = $item;
            if (($field['required'] ?? false)) {
                $value = $config[$name] ?? null;
                if ($value === null || $value === '') {
                    $missingFields[] = $item;
                }
            }
        }

        return [
            'missing_fields' => $missingFields,
            'expected_fields' => $expectedFields,
        ];
    }

    /**
     * Системные настройки экземпляра триггера (name, description, required).
     * Переопределяется в дочерних классах при необходимости.
     */
    public function getConfigFields(): array
    {
        return [];
    }

    protected function getValidationErrorMessage(string $fieldName): string
    {
        return __("Поле ':field' обязательно для выполнения триггера", ['field' => $fieldName]);
    }

    /**
     * Список ожидаемых полей триггера (name, description, required, is_internal).
     * Реализуется в дочерних классах.
     */
    protected function findSuccessfulExecutionLog(TriggerContext $context): ?object
    {
        return $context->grantedPermission->executionLogs()
            ->where('permission_trigger_id', function ($query) {
                $query->select('id')
                    ->from('permission_triggers')
                    ->where('class_name', static::class)
                    ->limit(1);
            })
            ->where('status', 'success')
            ->first();
    }

    abstract public function getRequiredFields(): array;

    abstract protected function perform(TriggerContext $context): TriggerResult;
}
