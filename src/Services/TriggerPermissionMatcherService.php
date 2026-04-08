<?php

namespace ArcheeNic\PermissionRegistry\Services;

use ArcheeNic\PermissionRegistry\Models\PermissionTriggerAssignment;
use Illuminate\Support\Collection;

class TriggerPermissionMatcherService
{
    /**
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

        return $this->collectManagedGrantAssignments($triggerClassPatterns)
            ->filter(function (PermissionTriggerAssignment $assignment) use ($normalizedDepartments): bool {
                $departmentId = $this->extractDepartmentId($assignment);

                return $departmentId !== null && in_array($departmentId, $normalizedDepartments, true);
            })
            ->map(function (PermissionTriggerAssignment $assignment): array {
                return [
                    'permission_id' => (int) $assignment->permission_id,
                    'permission_name' => (string) ($assignment->permission?->name ?? ''),
                    'department_id' => (string) $this->extractDepartmentId($assignment),
                ];
            })
            ->unique(fn (array $item) => $item['permission_id'] . ':' . $item['department_id'])
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
            ->filter(fn (PermissionTriggerAssignment $assignment): bool => $this->extractDepartmentId($assignment) !== null)
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
