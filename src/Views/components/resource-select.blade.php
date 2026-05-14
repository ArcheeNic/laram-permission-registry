@props([
    'resources' => [],
    'selected' => [],
    'name' => null,
    'wireModel' => null,
    'resourceKind' => null,
    'placeholder' => null,
    'emptyHint' => null,
    'id' => null,
])

@php
    $componentId = $id ?: 'resource-select-' . uniqid();
    $resourceItems = collect($resources)->map(fn ($r) => [
        'id' => (int) $r->id,
        'name' => (string) $r->name,
        'external_id' => $r->external_id ? (string) $r->external_id : '',
    ])->values();
    $selectedIds = collect($selected ?? [])->map(fn ($id) => (int) $id)->values()->all();
    $placeholderText = $placeholder ?? __('permission-registry::Select resources');
    $emptyText = $emptyHint ?? __('permission-registry::No resources discovered yet — run sync first');
    $searchPlaceholder = __('permission-registry::Search resources');
    $selectAllLabel = __('permission-registry::Select All');
    $deselectAllLabel = __('permission-registry::Deselect All');
    $nothingFoundText = __('permission-registry::Nothing found');
    $selectedSummaryTemplate = __('permission-registry::Selected: :count of :total');
    $totalCount = $resourceItems->count();
@endphp

@if($resourceItems->isEmpty())
    <p class="text-xs text-amber-600 dark:text-amber-400">{{ $emptyText }}</p>
@else
    <div
        x-data="{
            open: false,
            search: '',
            initialSelected: @js($selectedIds),
            total: {{ $totalCount }},
            selectedCount() {
                return this.$root.querySelectorAll('.pr-resource-checkbox:checked').length;
            },
            summary() {
                const n = this.selectedCount();
                if (n === 0) return @js($placeholderText);
                return @js($selectedSummaryTemplate).replace(':count', n).replace(':total', this.total);
            },
            matches(name, ext) {
                const q = this.search.trim().toLowerCase();
                if (!q) return true;
                return (name || '').toLowerCase().includes(q) || (ext || '').toLowerCase().includes(q);
            },
            visibleCount() {
                let n = 0;
                this.$root.querySelectorAll('.pr-resource-option').forEach(el => {
                    if (el.style.display !== 'none') n++;
                });
                return n;
            },
            toggleAll(value) {
                this.$root.querySelectorAll('.pr-resource-option').forEach(el => {
                    if (el.style.display === 'none') return;
                    const cb = el.querySelector('.pr-resource-checkbox');
                    if (!cb) return;
                    if (cb.checked !== value) {
                        cb.checked = value;
                        cb.dispatchEvent(new Event('change', { bubbles: true }));
                        cb.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });
            },
        }"
        x-on:keydown.escape.window="open = false"
        x-on:click.outside="open = false"
        class="relative"
    >
        <button type="button"
                x-on:click="open = !open"
                class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm bg-white dark:bg-neutral-800 border border-gray-300 dark:border-neutral-600 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-neutral-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <span class="truncate text-gray-700 dark:text-gray-200" x-text="summary()"></span>
            <div class="flex items-center gap-2">
                @if($resourceKind)
                    <span class="text-xs font-mono text-gray-400 dark:text-gray-500">{{ $resourceKind }}</span>
                @endif
                <svg class="w-4 h-4 text-gray-400 dark:text-gray-500 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </button>

        <div x-show="open"
             x-cloak
             x-transition.opacity.duration.100ms
             class="absolute z-30 mt-1 w-full bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-600 rounded-md shadow-lg">
            <div class="p-2 border-b border-gray-200 dark:border-neutral-700">
                <div class="relative">
                    <svg class="w-4 h-4 absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
                    </svg>
                    <input type="text"
                           x-model="search"
                           x-ref="searchInput"
                           x-on:click.stop
                           placeholder="{{ $searchPlaceholder }}"
                           class="w-full pl-8 pr-2 py-1.5 text-sm bg-gray-50 dark:bg-neutral-700 border border-gray-200 dark:border-neutral-600 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 text-gray-700 dark:text-gray-200">
                </div>
                <div class="flex items-center justify-between mt-2 text-xs">
                    <span class="text-gray-500 dark:text-gray-400" x-text="summary()"></span>
                    <div class="flex gap-2">
                        <button type="button" x-on:click.stop="toggleAll(true)" class="text-blue-600 dark:text-blue-300 hover:underline">{{ $selectAllLabel }}</button>
                        <button type="button" x-on:click.stop="toggleAll(false)" class="text-blue-600 dark:text-blue-300 hover:underline">{{ $deselectAllLabel }}</button>
                    </div>
                </div>
            </div>

            <div class="max-h-64 overflow-y-auto p-1">
                @foreach($resourceItems as $resource)
                    <label
                        class="pr-resource-option flex items-center gap-2 px-2 py-1.5 text-sm rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-neutral-700 text-gray-700 dark:text-gray-200"
                        x-show="matches(@js($resource['name']), @js($resource['external_id']))"
                    >
                        <input type="checkbox"
                               class="pr-resource-checkbox h-4 w-4 text-blue-600 dark:text-blue-300 border-gray-300 dark:border-neutral-500 rounded focus:ring-blue-500"
                               value="{{ $resource['id'] }}"
                               @if($wireModel) wire:model.live="{{ $wireModel }}" @endif
                               @if($name) name="{{ $name }}" @endif
                               @if(in_array($resource['id'], $selectedIds, true)) checked @endif>
                        <span class="truncate">{{ $resource['name'] }}</span>
                        @if($resource['external_id'])
                            <span class="ml-auto text-xs font-mono text-gray-400 dark:text-gray-500">{{ $resource['external_id'] }}</span>
                        @endif
                    </label>
                @endforeach
                <p x-show="visibleCount() === 0" class="px-2 py-3 text-xs text-gray-500 dark:text-gray-400 text-center">{{ $nothingFoundText }}</p>
            </div>
        </div>
    </div>
@endif
