<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Models\Permission;
use Illuminate\Support\Facades\DB;

class CopyPermissionAction
{
    public function handle(Permission $source): Permission
    {
        return DB::transaction(function () use ($source): Permission {
            $source->loadMissing([
                'fields',
                'triggerAssignments',
                'dependencies',
                'approvalPolicy.approvers',
            ]);

            $copy = $source->replicate(['id', 'created_at', 'updated_at']);
            $copy->name = $this->generateUniqueName($source);
            $copy->save();

            $copy->fields()->sync($source->fields->pluck('id')->all());

            foreach ($source->triggerAssignments as $assignment) {
                $copy->triggerAssignments()->create($assignment->only([
                    'permission_trigger_id',
                    'event_type',
                    'order',
                    'is_enabled',
                    'config',
                ]));
            }

            foreach ($source->dependencies as $dependency) {
                $copy->dependencies()->create($dependency->only([
                    'required_permission_id',
                    'is_strict',
                    'event_type',
                ]));
            }

            $policy = $source->approvalPolicy;
            if ($policy) {
                $copiedPolicy = $copy->approvalPolicy()->create($policy->only([
                    'approval_type',
                    'required_count',
                    'is_active',
                ]));

                foreach ($policy->approvers as $approver) {
                    $copiedPolicy->approvers()->create($approver->only([
                        'approver_type',
                        'approver_id',
                    ]));
                }
            }

            return $copy;
        });
    }

    private function generateUniqueName(Permission $source): string
    {
        $suffix = __('permission-registry::(copy)');
        $baseName = $source->name;
        $candidate = $baseName . ' ' . $suffix;

        $index = 2;
        while ($this->nameExists($source->service, $candidate)) {
            $candidate = $baseName . ' ' . $this->numberedSuffix($suffix, $index);
            $index++;
        }

        return $candidate;
    }

    private function numberedSuffix(string $suffix, int $index): string
    {
        if (preg_match('/^\((.*)\)$/u', $suffix, $matches) === 1) {
            return '(' . trim($matches[1]) . ' ' . $index . ')';
        }

        return $suffix . ' ' . $index;
    }

    private function nameExists(string $service, string $name): bool
    {
        return Permission::query()
            ->where('service', $service)
            ->where('name', $name)
            ->exists();
    }
}
