<div class="space-y-6">
    @if($flashMessage)
        <div class="p-3 rounded-lg border border-emerald-200 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 text-sm">
            {{ $flashMessage }}
        </div>
    @endif

    @if($flashError)
        <div class="p-3 rounded-lg border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 text-sm">
            {{ $flashError }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
        <div class="p-4 rounded-xl bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700">
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('permission-registry::messages.deactivated_users_with_pending') }}</div>
            <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $summary['users_count'] }}</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700">
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('permission-registry::messages.total_pending_permissions') }}</div>
            <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $summary['permissions_count'] }}</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700">
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('permission-registry::governance.management_mode.automated') }}</div>
            <div class="text-2xl font-semibold text-blue-700 dark:text-blue-300">{{ $summary['automated_count'] }}</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700">
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('permission-registry::governance.management_mode.manual') }}</div>
            <div class="text-2xl font-semibold text-orange-700 dark:text-orange-300">{{ $summary['manual_count'] }}</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700">
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('permission-registry::governance.management_mode.declarative') }}</div>
            <div class="text-2xl font-semibold text-purple-700 dark:text-purple-300">{{ $summary['declarative_count'] }}</div>
        </div>
    </div>

    <div class="bg-white dark:bg-neutral-800 rounded-xl shadow border border-gray-200 dark:border-neutral-700 p-4 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('permission-registry::messages.employee_category') }}</label>
                <select wire:model.live="employeeCategory" class="w-full rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-800 dark:text-gray-100 text-sm py-2 px-3">
                    <option value="">{{ __('permission-registry::All categories') }}</option>
                    @foreach($employeeCategories as $category)
                        <option value="{{ $category->value }}">{{ __('permission-registry::messages.employee_category_' . $category->value) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('permission-registry::Per page') }}</label>
                <select wire:model.live="perPage" class="w-full rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-800 dark:text-gray-100 text-sm py-2 px-3">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700 hidden md:table">
                <thead class="bg-gray-50 dark:bg-neutral-900">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">{{ __('permission-registry::User') }}</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">{{ __('permission-registry::messages.employee_category') }}</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">{{ __('permission-registry::messages.pending_by_mode') }}</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">{{ __('permission-registry::Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-neutral-700">
                    @forelse($rows as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700/40">
                            <td class="px-3 py-3">
                                <button wire:click="toggleExpanded({{ $row->id }})" class="text-left">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $row->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('permission-registry::messages.id_short') }}: {{ $row->id }}</div>
                                </button>
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ __('permission-registry::messages.employee_category_' . $row->employee_category->value) }}
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-700 dark:text-gray-300">
                                <div>{{ __('permission-registry::messages.automated_short') }}: {{ $row->pending_automated_count }}</div>
                                <div>{{ __('permission-registry::messages.manual_short') }}: {{ $row->pending_manual_count }}</div>
                                <div>{{ __('permission-registry::messages.declarative_short') }}: {{ $row->pending_declarative_count }}</div>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button wire:click="revokeAutomated({{ $row->id }})" class="px-3 py-1.5 rounded-md text-xs font-medium bg-blue-600 text-white hover:bg-blue-700">
                                        {{ __('permission-registry::messages.revoke_automated') }}
                                    </button>
                                    <button wire:click="createManualTasks({{ $row->id }})" class="px-3 py-1.5 rounded-md text-xs font-medium bg-orange-600 text-white hover:bg-orange-700">
                                        {{ __('permission-registry::messages.create_manual_revocation_tasks') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @if($expandedUserId === $row->id)
                            <tr>
                                <td colspan="4" class="px-3 py-3 bg-gray-50 dark:bg-neutral-900/50">
                                    <div class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-2">{{ __('permission-registry::messages.permissions_list') }}</div>
                                    <div class="space-y-2">
                                        @foreach($row->grantedPermissions as $grantedPermission)
                                            <div class="p-2 rounded-md bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $grantedPermission->permission?->name ?? __('permission-registry::messages.not_available') }}
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">({{ $grantedPermission->permission?->service ?? __('permission-registry::messages.not_available') }})</span>
                                                </div>
                                                <div class="text-xs text-gray-600 dark:text-gray-400">
                                                    {{ __('permission-registry::governance.management_mode_label') }}:
                                                    {{ $grantedPermission->permission?->management_mode?->label() ?? __('permission-registry::governance.management_mode.automated') }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('permission-registry::messages.no_pending_revocations') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="md:hidden space-y-3">
            @forelse($rows as $row)
                <div class="p-3 rounded-lg border border-gray-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 space-y-2">
                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $row->name }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('permission-registry::messages.employee_category_' . $row->employee_category->value) }}</div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">
                        {{ __('permission-registry::messages.automated_short') }}: {{ $row->pending_automated_count }}
                        /
                        {{ __('permission-registry::messages.manual_short') }}: {{ $row->pending_manual_count }}
                        /
                        {{ __('permission-registry::messages.declarative_short') }}: {{ $row->pending_declarative_count }}
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button wire:click="revokeAutomated({{ $row->id }})" class="px-2.5 py-1.5 rounded-md text-xs font-medium bg-blue-600 text-white hover:bg-blue-700">
                            {{ __('permission-registry::messages.revoke_automated') }}
                        </button>
                        <button wire:click="createManualTasks({{ $row->id }})" class="px-2.5 py-1.5 rounded-md text-xs font-medium bg-orange-600 text-white hover:bg-orange-700">
                            {{ __('permission-registry::messages.create_manual_revocation_tasks') }}
                        </button>
                    </div>
                </div>
            @empty
                <div class="text-center text-sm text-gray-500 dark:text-gray-400 py-4">
                    {{ __('permission-registry::messages.no_pending_revocations') }}
                </div>
            @endforelse
        </div>

        <div>
            {{ $rows->links() }}
        </div>
    </div>
</div>

