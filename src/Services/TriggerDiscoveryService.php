<?php

namespace ArcheeNic\PermissionRegistry\Services;

use ArcheeNic\PermissionRegistry\Contracts\PermissionTriggerInterface;
use Illuminate\Support\Facades\File;

class TriggerDiscoveryService
{
    /**
     * Сканировать namespace и получить список доступных триггеров с метаданными
     */
    public function discover(): array
    {
        $namespace = config('triggers.namespace');
        $directory = config('triggers.directory');

        if (!$namespace || !$directory || !File::isDirectory($directory)) {
            return [];
        }

        $triggers = [];
        $files = File::allFiles($directory);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = $this->getClassNameFromFile($file, $namespace, $directory);
            
            if (!$className || !class_exists($className)) {
                continue;
            }

            try {
                $reflection = new \ReflectionClass($className);
                
                if ($reflection->isAbstract() || $reflection->isInterface() || $reflection->isTrait()) {
                    continue;
                }

                if (!$reflection->implementsInterface(PermissionTriggerInterface::class)) {
                    continue;
                }

                $instance = app($className);
                
                // Проверить наличие методов метаданных (опциональные)
                $name = method_exists($instance, 'getName') ? $instance->getName() : $className;
                $description = method_exists($instance, 'getDescription') ? $instance->getDescription() : '';
                $requiredFields = method_exists($instance, 'getRequiredFields') ? $instance->getRequiredFields() : [];
                $configFields = method_exists($instance, 'getConfigFields') ? $instance->getConfigFields() : [];

                $triggers[] = [
                    'class_name' => $className,
                    'name' => $name,
                    'description' => $description,
                    'required_fields' => $requiredFields,
                    'config_fields' => $configFields,
                    'can_rollback' => $instance->canRollback(),
                    'is_configured' => true,
                ];
            } catch (\Throwable $e) {
                // Триггер не удалось инстанцировать (например, не настроен сервис) — добавляем с минимальными данными
                $triggers[] = [
                    'class_name' => $className,
                    'name' => class_basename($className),
                    'description' => '',
                    'required_fields' => [],
                    'config_fields' => [],
                    'can_rollback' => false,
                    'is_configured' => false,
                ];
            }
        }

        return $triggers;
    }

    /**
     * Получить полное имя класса из файла
     */
    private function getClassNameFromFile(\SplFileInfo $file, string $namespace, string $directory): ?string
    {
        $relativePath = str_replace($directory, '', $file->getRealPath());
        $relativePath = trim($relativePath, DIRECTORY_SEPARATOR);
        $relativePath = str_replace('.php', '', $relativePath);
        $relativePath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

        return $namespace . '\\' . $relativePath;
    }

    /**
     * Получить метаданные конкретного триггера
     */
    public function getTriggerMetadata(string $className): ?array
    {
        if (!class_exists($className)) {
            return null;
        }

        try {
            $reflection = new \ReflectionClass($className);
            
            if (!$reflection->implementsInterface(PermissionTriggerInterface::class)) {
                return null;
            }

            $instance = app($className);

            // Проверить наличие методов метаданных (опциональные)
            $name = method_exists($instance, 'getName') ? $instance->getName() : $className;
            $description = method_exists($instance, 'getDescription') ? $instance->getDescription() : '';
            $requiredFields = method_exists($instance, 'getRequiredFields') ? $instance->getRequiredFields() : [];
            $configFields = method_exists($instance, 'getConfigFields') ? $instance->getConfigFields() : [];

            return [
                'class_name' => $className,
                'name' => $name,
                'description' => $description,
                'required_fields' => $requiredFields,
                'config_fields' => $configFields,
                'can_rollback' => $instance->canRollback(),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}

