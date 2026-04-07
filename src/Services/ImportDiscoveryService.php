<?php

namespace ArcheeNic\PermissionRegistry\Services;

use ArcheeNic\PermissionRegistry\Contracts\PermissionImportInterface;
use Illuminate\Support\Facades\File;

class ImportDiscoveryService
{
    /**
     * Сканировать namespace и получить список доступных импортов с метаданными
     */
    public function discover(): array
    {
        $namespace = config('imports.namespace');
        $directory = config('imports.directory');

        if (!$namespace || !$directory || !File::isDirectory($directory)) {
            return [];
        }

        $imports = [];
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

                if (!$reflection->implementsInterface(PermissionImportInterface::class)) {
                    continue;
                }

                $instance = app($className);

                $imports[] = [
                    'class_name' => $className,
                    'name' => $instance->getName(),
                    'description' => $instance->getDescription(),
                    'required_fields' => $instance->getRequiredFields(),
                    'config_fields' => $instance->getConfigFields(),
                    'is_configured' => true,
                ];
            } catch (\Throwable $e) {
                $imports[] = [
                    'class_name' => $className,
                    'name' => class_basename($className),
                    'description' => '',
                    'required_fields' => [],
                    'config_fields' => [],
                    'is_configured' => false,
                ];
            }
        }

        return $imports;
    }

    private function getClassNameFromFile(\SplFileInfo $file, string $namespace, string $directory): ?string
    {
        $relativePath = str_replace($directory, '', $file->getRealPath());
        $relativePath = trim($relativePath, DIRECTORY_SEPARATOR);
        $relativePath = str_replace('.php', '', $relativePath);
        $relativePath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

        return $namespace . '\\' . $relativePath;
    }

    /**
     * Получить метаданные конкретного импорта
     */
    public function getImportMetadata(string $className): ?array
    {
        if (!class_exists($className)) {
            return null;
        }

        try {
            $reflection = new \ReflectionClass($className);

            if (!$reflection->implementsInterface(PermissionImportInterface::class)) {
                return null;
            }

            $instance = app($className);

            return [
                'class_name' => $className,
                'name' => $instance->getName(),
                'description' => $instance->getDescription(),
                'required_fields' => $instance->getRequiredFields(),
                'config_fields' => $instance->getConfigFields(),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}
