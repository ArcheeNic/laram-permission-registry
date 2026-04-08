<?php

namespace ArcheeNic\PermissionRegistry\Services;

use ArcheeNic\PermissionRegistry\Models\PermissionTriggerAssignment;
use Illuminate\Support\Collection;

class TriggerOverlapDetectorService
{
    /**
     * @param int|null $permissionId
     * @param array<int, string> $triggerClassPatterns
     * @return array<string, array<int, array{permission_id: int, permission_name: string, assignment_id: int}>>
     */
    public function detectOverlaps(?int $permissionId = null, array $triggerClassPatterns = ['App\\Triggers\\Bitrix24%']): array
    {
        $matcher = app(TriggerPermissionMatcherService::class);

        $assignments = PermissionTriggerAssignment::query()
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
            ->map(function (PermissionTriggerAssignment $assignment) use ($matcher): ?array {
                $config = $assignment->{PermissionTriggerAssignment::CONFIG};
                $departmentId = is_array($config) ? ($config['department_id'] ?? null) : null;
                $normalizedDepartmentId = $matcher->normalizeDepartmentIds($departmentId === null ? [] : [(string) $departmentId])[0] ?? null;

                if ($normalizedDepartmentId === null) {
                    return null;
                }

                return [
                    'department_id' => $normalizedDepartmentId,
                    'permission_id' => (int) $assignment->permission_id,
                    'permission_name' => (string) ($assignment->permission?->name ?? ''),
                    'assignment_id' => (int) $assignment->id,
                ];
            })
            ->filter()
            ->values();

        $grouped = $assignments
            ->groupBy('department_id')
            ->map(function (Collection $items): array {
                return $items->map(fn (array $item): array => [
                    'permission_id' => $item['permission_id'],
                    'permission_name' => $item['permission_name'],
                    'assignment_id' => $item['assignment_id'],
                ])->all();
            })
            ->filter(fn (array $items): bool => count(array_unique(array_column($items, 'permission_id'))) > 1)
            ->toArray();

        if ($permissionId === null) {
            return $grouped;
        }

        return array_filter(
            $grouped,
            static fn (array $items): bool => in_array($permissionId, array_column($items, 'permission_id'), true)
        );
    }
}
