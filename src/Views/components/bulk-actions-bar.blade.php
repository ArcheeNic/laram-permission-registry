@php
    $actionButtonClass = function (string $action) {
        $base = 'inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors';
        $active = [
            'hire' => 'bg-green-600 text-white hover:bg-green-700',
            'group' => 'bg-purple-600 text-white hover:bg-purple-700',
            'position' => 'bg-blue-600 text-white hover:bg-blue-700',
            'fire' => 'bg-red-600 text-white hover:bg-red-700',
        ];
        $inactive = 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-neutral-700 dark:text-gray-200 dark:hover:bg-neutral-600';

        return $base . ' ' . ($this->bulkAction === $action ? $active[$action] : $inactive);
    };
@endphp

<div class="fixed bottom-0 left-0 right-0 z-40 border-t border-gray-200 bg-white/95 shadow-lg backdrop-blur dark:border-neutral-700 dark:bg-neutral-800/95">
    <div class="mx-auto w-full max-w-7xl px-4 py-3">
        <div class="flex flex-wrap items-center gap-3">
            <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ __('permission-registry::messages.bulk_selected_count', ['count' => $this->bulkSelectedCount]) }}
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" wire:click="setBulkAction('hire')" class="{{ $actionButtonClass('hire') }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    {{ __('permission-registry::messages.hire') }}
                    <span class="ml-1 rounded-full bg-black/10 px-1.5 py-0.5 text-xs">{{ $this->bulkHireEligibleCount }}/{{ $this->bulkSelectedCount }}</span>
                </button>
                <button type="button" wire:click="setBulkAction('group')" class="{{ $actionButtonClass('group') }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 3a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    {{ __('permission-registry::messages.assign_group') }}
                </button>
                <button type="button" wire:click="setBulkAction('position')" class="{{ $actionButtonClass('position') }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    {{ __('permission-registry::messages.assign_position') }}
                </button>
                <button type="button" wire:click="setBulkAction('fire')" class="{{ $actionButtonClass('fire') }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6m0 0l6-6m-6 6l6 6"/>
                    </svg>
                    {{ __('permission-registry::messages.fire') }}
                    <span class="ml-1 rounded-full bg-black/10 px-1.5 py-0.5 text-xs">{{ $this->bulkFireEligibleCount }}/{{ $this->bulkSelectedCount }}</span>
                </button>
            </div>

            <button type="button"
                    wire:click="clearBulkSelection"
                    class="ml-auto rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-neutral-600 dark:text-gray-200 dark:hover:bg-neutral-700">
                {{ __('permission-registry::messages.clear_selection') }}
            </button>
        </div>

        @if($this->bulkAction !== '')
            <div class="mt-3 flex flex-wrap items-end gap-2 border-t border-gray-100 pt-3 dark:border-neutral-700">
                @if($this->bulkAction === 'hire')
                    <div class="flex-1 min-w-[200px]">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                            {{ __('permission-registry::messages.employee_category') }}
                        </label>
                        <select wire:model="selectedHireCategory"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-neutral-600 dark:bg-neutral-800 dark:text-gray-100">
                            @foreach($this->employeeCategoryOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button"
                            wire:click="bulkHireUsers"
                            wire:loading.attr="disabled"
                            wire:target="bulkHireUsers"
                            @disabled($this->bulkHireEligibleCount === 0)
                            class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50">
                        {{ __('permission-registry::messages.hire') }}
                        <span class="ml-1 text-xs opacity-80">({{ $this->bulkHireEligibleCount }})</span>
                    </button>
                    @if($this->bulkHireEligibleCount === 0)
                        <span class="text-xs text-amber-600 dark:text-amber-400">
                            {{ __('permission-registry::messages.bulk_no_eligible_hire') }}
                        </span>
                    @endif
                @elseif($this->bulkAction === 'group')
                    <div class="flex-1 min-w-[200px]">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                            {{ __('permission-registry::Group') }}
                        </label>
                        <select wire:model="bulkGroupId"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-neutral-600 dark:bg-neutral-800 dark:text-gray-100">
                            <option value="">{{ __('permission-registry::Select a group') }}</option>
                            @foreach($availableGroups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button"
                            wire:click="bulkAssignGroup"
                            wire:loading.attr="disabled"
                            wire:target="bulkAssignGroup"
                            @disabled($bulkGroupId === '')
                            class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700 disabled:cursor-not-allowed disabled:opacity-50">
                        {{ __('permission-registry::messages.apply') }}
                    </button>
                @elseif($this->bulkAction === 'position')
                    <div class="flex-1 min-w-[200px]">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                            {{ __('permission-registry::Position') }}
                        </label>
                        <select wire:model="bulkPositionId"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-neutral-600 dark:bg-neutral-800 dark:text-gray-100">
                            <option value="">{{ __('permission-registry::Select a position') }}</option>
                            @foreach($availablePositions as $position)
                                <option value="{{ $position->id }}">{{ $position->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button"
                            wire:click="bulkAssignPosition"
                            wire:loading.attr="disabled"
                            wire:target="bulkAssignPosition"
                            @disabled($bulkPositionId === '')
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                        {{ __('permission-registry::messages.apply') }}
                    </button>
                @elseif($this->bulkAction === 'fire')
                    <div class="flex-1 min-w-[200px] text-sm text-gray-700 dark:text-gray-200">
                        @if($this->bulkFireEligibleCount > 0)
                            {{ __('permission-registry::messages.bulk_confirm_fire') }}
                        @else
                            <span class="text-amber-600 dark:text-amber-400">
                                {{ __('permission-registry::messages.bulk_no_eligible_fire') }}
                            </span>
                        @endif
                    </div>
                    <button type="button"
                            wire:click="bulkFireUsers"
                            wire:loading.attr="disabled"
                            wire:target="bulkFireUsers"
                            data-confirm-message="{{ __('permission-registry::messages.bulk_confirm_fire') }}"
                            onclick="return confirm(this.dataset.confirmMessage)"
                            @disabled($this->bulkFireEligibleCount === 0)
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50">
                        {{ __('permission-registry::messages.fire') }}
                        <span class="ml-1 text-xs opacity-80">({{ $this->bulkFireEligibleCount }})</span>
                    </button>
                @endif
            </div>
        @endif
    </div>
</div>
