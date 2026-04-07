<?php

namespace ArcheeNic\PermissionRegistry\Services;

use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use ArcheeNic\PermissionRegistry\ValueObjects\DependencyValidationResult;
use Illuminate\Support\Facades\Log;

class PermissionDependencyResolver
{
    /**
     * Проверить зависимости между правами
     */
    public function validatePermissionDependencies(int $virtualUserId, Permission $permission, string $eventType = 'grant'): DependencyValidationResult
    {
        $dependencies = $permission->dependencies()
            ->where('event_type', $eventType)
            ->with('requiredPermission')
            ->get();

        if ($dependencies->isEmpty()) {
            return DependencyValidationResult::valid();
        }

        $missingPermissions = [];

        foreach ($dependencies as $dependency) {
            $requiredPermission = $dependency->requiredPermission;

            if ($dependency->is_strict) {
                // Строгая зависимость: право должно быть полностью выдано
                $hasPermission = GrantedPermission::where('virtual_user_id', $virtualUserId)
                    ->where('permission_id', $requiredPermission->id)
                    ->where('enabled', true)
                    ->where('status', 'granted')
                    ->exists();

                if (!$hasPermission) {
                    $missingPermissions[] = [
                        'id' => $requiredPermission->id,
                        'name' => $requiredPermission->name,
                        'service' => $requiredPermission->service,
                        'is_strict' => true,
                    ];
                }
            } else {
                // Не строгая зависимость: достаточно наличия полей
                $requiredFields = $requiredPermission->fields()
                    ->where('is_global', true)
                    ->get();

                foreach ($requiredFields as $field) {
                    $hasValue = VirtualUserFieldValue::where('virtual_user_id', $virtualUserId)
                        ->where('permission_field_id', $field->id)
                        ->whereNotNull('value')
                        ->exists();

                    if (!$hasValue) {
                        $missingPermissions[] = [
                            'id' => $requiredPermission->id,
                            'name' => $requiredPermission->name,
                            'service' => $requiredPermission->service,
                            'is_strict' => false,
                            'reason' => "Не заполнено поле: {$field->name}",
                        ];
                        break; // Достаточно одного отсутствующего поля
                    }
                }
            }
        }

        if (!empty($missingPermissions)) {
            return DependencyValidationResult::invalid($missingPermissions);
        }

        return DependencyValidationResult::valid();
    }

    /**
     * Проверить глобальные поля
     */
    public function validateGlobalFields(int $virtualUserId, Permission $permission): DependencyValidationResult
    {
        $globalFields = $permission->fields()
            ->where('is_global', true)
            ->get();

        if ($globalFields->isEmpty()) {
            return DependencyValidationResult::valid();
        }

        $missingFields = [];

        foreach ($globalFields as $field) {
            $hasValue = VirtualUserFieldValue::where('virtual_user_id', $virtualUserId)
                ->where('permission_field_id', $field->id)
                ->whereNotNull('value')
                ->exists();

            if (!$hasValue) {
                $missingFields[] = [
                    'id' => $field->id,
                    'name' => $field->name,
                ];
            }
        }

        if (!empty($missingFields)) {
            return DependencyValidationResult::invalid([], $missingFields);
        }

        return DependencyValidationResult::valid();
    }

    /**
     * Проверить глобальные поля с учётом переданных значений (для валидации перед выдачей)
     *
     * @param array $fieldValuesByFieldId значения по permission_field_id (из формы)
     */
    public function validateGlobalFieldsWithValues(
        int $virtualUserId,
        Permission $permission,
        array $fieldValuesByFieldId = []
    ): DependencyValidationResult {
        $globalFields = $permission->fields()
            ->where('is_global', true)
            ->get();

        if ($globalFields->isEmpty()) {
            return DependencyValidationResult::valid();
        }

        $missingFields = [];

        foreach ($globalFields as $field) {
            $hasValue = false;
            if (isset($fieldValuesByFieldId[$field->id]) && $fieldValuesByFieldId[$field->id] !== '' && $fieldValuesByFieldId[$field->id] !== null) {
                $hasValue = true;
            }
            if (!$hasValue) {
                $hasValue = VirtualUserFieldValue::where('virtual_user_id', $virtualUserId)
                    ->where('permission_field_id', $field->id)
                    ->whereNotNull('value')
                    ->exists();
            }
            if (!$hasValue) {
                $missingFields[] = [
                    'id' => $field->id,
                    'name' => $field->name,
                ];
            }
        }

        if (!empty($missingFields)) {
            return DependencyValidationResult::invalid([], $missingFields);
        }

        return DependencyValidationResult::valid();
    }

    /**
     * Можно ли выдать право (проверка всего)
     */
    public function canGrantPermission(int $virtualUserId, Permission $permission): bool
    {
        $dependencyResult = $this->validatePermissionDependencies($virtualUserId, $permission);
        $fieldsResult = $this->validateGlobalFields($virtualUserId, $permission);

        return $dependencyResult->isValid && $fieldsResult->isValid;
    }

    /**
     * Получить причины блокировки
     */
    public function getBlockingReasons(int $virtualUserId, Permission $permission): array
    {
        $reasons = [];

        $dependencyResult = $this->validatePermissionDependencies($virtualUserId, $permission);
        if (!$dependencyResult->isValid) {
            $reasons[] = [
                'type' => 'dependencies',
                'message' => $dependencyResult->getErrorMessage(),
                'details' => $dependencyResult->missingPermissions,
            ];
        }

        $fieldsResult = $this->validateGlobalFields($virtualUserId, $permission);
        if (!$fieldsResult->isValid) {
            $reasons[] = [
                'type' => 'fields',
                'message' => $fieldsResult->getErrorMessage(),
                'details' => $fieldsResult->missingFields,
            ];
        }

        return $reasons;
    }

    /**
     * Получить список прав, которые станут доступны после выдачи данного права
     */
    public function getDependentPermissions(Permission $permission): array
    {
        return $permission->dependents()
            ->with('permission')
            ->get()
            ->map(fn($dep) => $dep->permission)
            ->unique('id')
            ->values()
            ->toArray();
    }

    /**
     * Сортировать права по зависимостям (топологическая сортировка)
     * 
     * @param array $permissionIds Массив ID прав для сортировки
     * @param string $eventType Тип события: 'grant' или 'revoke'
     * @return array Отсортированный массив ID прав
     *               - grant: сначала права без зависимостей (базовые → зависимые)
     *               - revoke: сначала зависимые права (зависимые → базовые)
     * @throws \RuntimeException При обнаружении циклических зависимостей
     */
    public function sortByDependencies(array $permissionIds, string $eventType = 'grant'): array
    {
        if (empty($permissionIds)) {
            return [];
        }

        Log::debug('sortByDependencies: начало', [
            'permissionIds' => $permissionIds,
            'eventType' => $eventType
        ]);

        // Загрузить права с их зависимостями для указанного event_type
        $permissions = Permission::whereIn('id', $permissionIds)
            ->with(['dependencies' => function ($query) use ($permissionIds, $eventType) {
                // Учитываем только зависимости внутри текущего набора прав и для указанного event_type
                $query->whereIn('required_permission_id', $permissionIds)
                    ->where('event_type', $eventType);
            }])
            ->get()
            ->keyBy('id');

        Log::debug('sortByDependencies: загружены права', [
            'permissions' => $permissions->pluck('name', 'id')->toArray(),
            'dependencies' => $permissions->mapWithKeys(function ($perm) {
                return [$perm->id => $perm->dependencies->pluck('required_permission_id')->toArray()];
            })->toArray()
        ]);

        // Построить граф: для каждого права - список его зависимостей
        $graph = [];
        $inDegree = []; // Количество входящих ребер (зависимостей)

        foreach ($permissionIds as $permId) {
            $graph[$permId] = [];
            $inDegree[$permId] = 0;
        }

        // Заполнить граф и подсчитать входящие ребра
        foreach ($permissions as $permission) {
            foreach ($permission->dependencies as $dependency) {
                $requiredId = $dependency->required_permission_id;
                
                // Добавить ребро: requiredId -> permission->id
                if (isset($graph[$requiredId])) {
                    $graph[$requiredId][] = $permission->id;
                    $inDegree[$permission->id]++;
                }
            }
        }

        // Топологическая сортировка (алгоритм Кана)
        $queue = [];
        $result = [];

        // Начать с прав без зависимостей
        foreach ($inDegree as $permId => $degree) {
            if ($degree === 0) {
                $queue[] = $permId;
            }
        }

        while (!empty($queue)) {
            $current = array_shift($queue);
            $result[] = $current;

            // Уменьшить степень для всех зависимых прав
            foreach ($graph[$current] as $dependent) {
                $inDegree[$dependent]--;
                
                if ($inDegree[$dependent] === 0) {
                    $queue[] = $dependent;
                }
            }
        }

        // Проверить на циклические зависимости
        if (count($result) !== count($permissionIds)) {
            $unsorted = array_diff($permissionIds, $result);
            $unsortedNames = Permission::whereIn('id', $unsorted)->pluck('name', 'id')->toArray();
            
            throw new \RuntimeException(
                'Обнаружены циклические зависимости между правами: ' . 
                implode(', ', $unsortedNames)
            );
        }

        // Для revoke нужен обратный порядок (сначала зависимые, потом базовые)
        if ($eventType === 'revoke') {
            $result = array_reverse($result);
        }

        $resultNames = Permission::whereIn('id', $result)->get()->pluck('name', 'id')->toArray();
        $sortedNames = array_map(fn($id) => $resultNames[$id] ?? $id, $result);
        
        Log::debug('sortByDependencies: результат', [
            'original' => $permissionIds,
            'sorted' => $result,
            'sorted_names' => $sortedNames,
            'eventType' => $eventType
        ]);

        return $result;
    }
}
