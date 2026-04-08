@php
    $filters = [
        null => ['label' => __('permission-registry::messages.import.filter_all'), 'count' => $stagingStats['total'], 'classes' => 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-neutral-700 dark:text-gray-300 dark:hover:bg-neutral-600'],
        'new' => ['label' => __('permission-registry::messages.import.status_new'), 'count' => $stagingStats['new'], 'classes' => 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-300 dark:hover:bg-green-900/50'],
        'changed' => ['label' => __('permission-registry::messages.import.status_changed'), 'count' => $stagingStats['changed'], 'classes' => 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300 dark:hover:bg-yellow-900/50'],
        'exists' => ['label' => __('permission-registry::messages.import.status_exists'), 'count' => $stagingStats['exists'], 'classes' => 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'],
        'missing' => ['label' => __('permission-registry::messages.import.status_missing'), 'count' => $stagingStats['missing'], 'classes' => 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50'],
    ];
@endphp
<div class="p-4 border-b dark:border-neutral-700 flex flex-wrap gap-2 items-center">
    @foreach($filters as $value => $filter)
        <button
            wire:click="{{ $value === null ? 'clearStatusFilter' : "setStatusFilter('$value')" }}"
            class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $filter['classes'] }}
                   {{ ($statusFilter ?? null) === $value || ($value === null && $statusFilter === null) ? 'ring-2 ring-indigo-500 ring-offset-1 dark:ring-offset-neutral-800' : '' }}">
            {{ $filter['label'] }}
            <span class="ml-1 font-bold">{{ $filter['count'] }}</span>
        </button>
    @endforeach

    <div class="w-full flex flex-wrap gap-2 items-center pt-2 border-t border-gray-100 dark:border-neutral-700">
        <select wire:model.live="grantPermissionFilterId"
                class="rounded-md border-gray-300 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-200 text-xs min-w-[180px]">
            <option value="">{{ __('permission-registry::messages.import.filter_grant_right') }}</option>
            @foreach(($managedPermissions ?? collect()) as $permission)
                <option value="{{ $permission->id }}">{{ $permission->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="revokePermissionFilterId"
                class="rounded-md border-gray-300 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-200 text-xs min-w-[180px]">
            <option value="">{{ __('permission-registry::messages.import.filter_revoke_right') }}</option>
            @foreach(($managedPermissions ?? collect()) as $permission)
                <option value="{{ $permission->id }}">{{ $permission->name }}</option>
            @endforeach
        </select>

        @if($grantPermissionFilterId || $revokePermissionFilterId)
            <button wire:click="clearPermissionFilters"
                    class="px-2.5 py-1.5 text-xs font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-neutral-700 dark:text-gray-300 dark:hover:bg-neutral-600">
                {{ __('permission-registry::messages.import.clear_rights_filter') }}
            </button>
        @endif

        @if(($managedPermissions ?? collect())->isEmpty())
            <span class="text-xs text-gray-400 dark:text-gray-500">
                {{ __('permission-registry::messages.import.no_rights_for_filter') }}
            </span>
        @endif
    </div>

    @if($stagingStats['total'] > 0)
        <span class="text-xs text-gray-400 dark:text-gray-500">
            {{ __('permission-registry::messages.import.rows_shown', ['count' => $stagingRows->count(), 'total' => $stagingStats['total']]) }}
        </span>
    @endif
</div>
