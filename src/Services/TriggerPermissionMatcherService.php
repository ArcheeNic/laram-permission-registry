<?php

namespace ArcheeNic\PermissionRegistry\Services;

use ArcheeNic\PermissionRegistry\Enums\PermissionScope;
use ArcheeNic\PermissionRegistry\Models\PermissionTriggerAssignment;
use Illuminate\Support\Collection;

class TriggerPermissionMatcherService
{
    /**
     * Возвращает пары (permission_id, department_id), которые должны быть выданы
     * пользователю с заданными department_ids.
     *
     * Для resource-scoped прав: department_id из row становится external_id для
     * resolve в каталоге permission_resources — упор делается на наличие хотя бы
     * одного включённого grant-триггера c matching class pattern.
     *
     * Для service-scoped (legacy): сохранена старая семантика — конкретный
     * department_id зашит в assignment.config['department_id'].
     *
     * @param array<int, string> $departmentIds
     * @param array<int, string> $triggerClassPatterns
     * @return Collection<int, array{permission_id: int, permission_name: string, department_id: string}>
     */
    public function matchByDepartments(array $departmentIds, array $triggerClassPatterns): Collection
    {
        $normalizedDepartments = $this->normalizeDepartmentIds($departmentIds);
        if ($normalizedDepartments === []) {
            return collect();
        }

        $assignments = $this->collectManagedGrantAssignments($triggerClassPatterns);

        $result = collect();

        foreach ($assignments as $assignment) {
            $permission = $assignment->permission;
            if ($permission === null) {
                continue;
            }
            $scope = $permission->scope ?? PermissionScope::Service;

            if ($scope === PermissionScope::Resource) {
                foreach ($normalizedDepartments as $extId) {
                    $result->push([
                        'permission_id' => (int) $assignment->permission_id,
                        'permission_name' => (string) $permission->name,
                        'department_id' => (string) $extId,
                    ]);
                }
                continue;
            }

            $configDeptId = $this->extractDepartmentId($assignment);
            if ($configDeptId === null) {
                continue;
            }
            if (!in_array($configDeptId, $normalizedDepartments, true)) {
                continue;
            }
            $result->push([
                'permission_id' => (int) $assignment->permission_id,
                'permission_name' => (string) $permission->name,
                'department_id' => $configDeptId,
            ]);
        }

        return $result
            ->unique(fn (array $item) => $item['permission_id'].':'.$item['department_id'])
            ->values();
    }

    /**
     * @param array<int, string> $triggerClassPatterns
     * @return array<int, int>
     */
    public function getAllManagedPermissionIds(array $triggerClassPatterns): array
    {
        return $this->collectManagedGrantAssignments($triggerClassPatterns)
            ->map(fn (PermissionTriggerAssignment $assignment): int => (int) $assignment->permission_id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function getFallbackPermissionIds(?string $fallbackTriggerClass): array
    {
        if ($fallbackTriggerClass === null || $fallbackTriggerClass === '') {
            return [];
        }

        return PermissionTriggerAssignment::query()
            ->with('trigger')
            ->where(PermissionTriggerAssignment::EVENT_TYPE, 'grant')
            ->where(PermissionTriggerAssignment::IS_ENABLED, true)
            ->get()
            ->filter(fn (PermissionTriggerAssignment $assignment): bool => (string) ($assignment->trigger?->class_name ?? '') === $fallbackTriggerClass)
            ->map(fn (PermissionTriggerAssignment $assignment): int => (int) $assignment->permission_id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Включает все assignment'ы (grant, enabled) с trigger class из patterns —
     * без фильтрации по config.department_id. Для resource-scoped прав config
     * обычно пуст, и фильтр по нему отрезал бы их от импорта.
     *
     * @param array<int, string> $triggerClassPatterns
     * @return Collection<int, PermissionTriggerAssignment>
     */
    private function collectManagedGrantAssignments(array $triggerClassPatterns): Collection
    {
        return PermissionTriggerAssignment::query()
            ->with(['trigger', 'permission'])
            ->where(PermissionTriggerAssignment::EVENT_TYPE, 'grant')
            ->where(PermissionTriggerAssignment::IS_ENABLED, true)
            ->get()
            ->filter(function (PermissionTriggerAssignment $assignment) use ($triggerClassPatterns): bool {
                $className = (string) ($assignment->trigger?->class_name ?? '');
                foreach ($triggerClassPatterns as $pattern) {
                    if (str($className)->is(str_replace('%', '*', $pattern))) {
                        return true;
                    }
                }

                return false;
            })
            ->values();
    }

    /**
     * @param array<int, string>|string|null $departments
     * @return array<int, string>
     */
    public function normalizeDepartmentIds(array|string|null $departments): array
    {
        if ($departments === null) {
            return [];
        }

        if (is_string($departments)) {
            $departments = preg_split('/\s*,\s*/', $departments) ?: [];
        }

        $normalized = array_map(
            static fn (mixed $item): string => trim((string) $item),
            $departments
        );

        return array_values(array_unique(array_filter($normalized, static fn (string $item): bool => $item !== '')));
    }

    private function extractDepartmentId(PermissionTriggerAssignment $assignment): ?string
    {
        $config = $assignment->{PermissionTriggerAssignment::CONFIG};
        $departmentId = is_array($config) ? ($config['department_id'] ?? null) : null;

        if ($departmentId === null || $departmentId === '') {
            return null;
        }

        return trim((string) $departmentId);
    }
}
