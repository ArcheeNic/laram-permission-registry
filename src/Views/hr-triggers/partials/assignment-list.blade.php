@props([
    'key',
    'employeeCategory',
    'eventType',
    'title',
    'description',
    'assignments',
    'availableTriggers',
    'notConfiguredTriggerIds' => [],
])

<div class="mb-4">
    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">{{ $title }}</h3>
    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $description }}</p>
</div>

<div class="space-y-2 mb-4">
    @forelse($assignments as $assignment)
        <div class="bg-gray-50 dark:bg-neutral-700 p-4 rounded-md flex items-center justify-between border border-gray-200 dark:border-neutral-600">
            <div class="flex items-center flex-1">
                <div>
                    <div class="font-medium text-gray-900 dark:text-gray-100">
                        {{ $assignment->trigger->name }}
                        @if(in_array($assignment->trigger->id, $notConfiguredTriggerIds))
                            <span class="text-amber-600 dark:text-amber-400 text-sm font-normal">— {{ __('permission-registry::messages.not_configured') }}</span>
                        @endif
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $assignment->trigger->class_name }}</div>
                    @if(!empty($assignment->config))
                        <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                            @foreach($assignment->config as $key => $val)
                                {{ $key }}: {{ $val }}
                                @if(!$loop->last)<span class="mx-1">·</span>@endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500">{{ __('permission-registry::messages.order') }}: {{ $assignment->order }}</span>
                <label class="inline-flex items-center">
                    <input type="checkbox"
                           class="toggle-trigger rounded text-purple-600"
                           data-id="{{ $assignment->id }}"
                           {{ $assignment->is_enabled ? 'checked' : '' }}>
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('permission-registry::messages.enabled') }}</span>
                </label>
                <button type="button"
                        data-id="{{ $assignment->id }}"
                        class="remove-trigger-btn text-red-600 hover:text-red-800">
                    🗑️
                </button>
            </div>
        </div>
    @empty
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            {{ __('permission-registry::messages.no_triggers_configured') }}
        </div>
    @endforelse
</div>

<div class="border-t border-gray-200 dark:border-neutral-700 pt-4">
    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">{{ __('permission-registry::messages.add_trigger') }}</h4>
    <div class="flex flex-wrap gap-2 items-end">
        <div class="flex-1 min-w-[200px]">
            <select id="{{ $key }}-trigger-select" data-trigger-select="1" data-key="{{ $key }}" class="w-full rounded-md border-gray-300 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-100">
                <option value="">{{ __('permission-registry::messages.select_trigger') }}</option>
                @foreach($availableTriggers as $trigger)
                    @php($notConfigured = in_array($trigger->id, $notConfiguredTriggerIds))
                    <option value="{{ $trigger->id }}" @if($notConfigured) disabled @endif>
                        {{ $trigger->name }}@if($notConfigured) — {{ __('permission-registry::messages.not_configured') }}@endif
                    </option>
                @endforeach
            </select>
        </div>
        <div id="{{ $key }}-config-fields" class="hidden w-full mt-2 space-y-2 p-3 bg-gray-100 dark:bg-neutral-600 rounded-md"></div>
        <button onclick="addTrigger('{{ $eventType }}', '{{ $employeeCategory }}', '{{ $key }}')"
                class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
            {{ __('permission-registry::messages.add_trigger') }}
        </button>
    </div>
</div>
