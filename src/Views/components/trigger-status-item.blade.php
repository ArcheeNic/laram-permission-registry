@props(['trigger' => [], 'grantedPermissionId' => null, 'index' => null, 'total' => null])

@php
    $statusLabels = [
        'success' => __('permission-registry::Completed'),
        'failed' => __('permission-registry::Failed'),
        'running' => __('permission-registry::Running'),
        'pending' => __('permission-registry::Pending'),
    ];
    $statusLabel = $statusLabels[$trigger['status']] ?? $trigger['status'];
    $stepInfo = ($index !== null && $total !== null) ? "({$index}/{$total})" : '';
@endphp

<div class="text-sm" role="listitem" aria-label="{{ $trigger['name'] }} - {{ $statusLabel }}">
    @if($trigger['status'] === 'success')
        <div class="flex items-center space-x-2 flex-shrink-0">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
        </div>
        <span class="text-gray-700 dark:text-gray-300">{{ $trigger['name'] }}</span>
        <span class="sr-only">{{ $statusLabel }}</span>
    @elseif($trigger['status'] === 'failed')
        <div class="flex flex-wrap items-start gap-2 sm:gap-3">
            <div class="flex items-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-gray-700 dark:text-gray-300 font-medium">
                    {{ $trigger['name'] }}
                    <span class="ml-1 px-1.5 py-0.5 text-xs bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 rounded">
                        {{ __('permission-registry::Error') }}
                    </span>
                </div>
                @if(!empty($trigger['error_message']))
                    <div class="mt-1 text-xs text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/30 p-2 rounded" role="alert">
                        <div class="font-semibold mb-1">{{ $trigger['error_message'] }}</div>
                        @if(config('app.debug') && isset($trigger['meta']) && is_array($trigger['meta']) && !empty($trigger['meta']))
                            <div class="mt-2 text-xs opacity-75 border-t border-red-200 dark:border-red-800 pt-2">
                                @if(isset($trigger['meta']['exception']))
                                    <div><strong>{{ __('permission-registry::Exception') }}:</strong> {{ $trigger['meta']['exception'] }}</div>
                                @endif
                                @if(isset($trigger['meta']['file']))
                                    <div><strong>{{ __('permission-registry::File') }}:</strong> {{ $trigger['meta']['file'] }}:{{ $trigger['meta']['line'] ?? '' }}</div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
                @php
                    $expectedFields = $trigger['meta']['expected_fields'] ?? $trigger['meta']['missing_fields'] ?? [];
                @endphp
                @if($grantedPermissionId && isset($trigger['trigger_id']) && !empty($expectedFields))
                    <div class="mt-3 pt-2 border-t border-red-200 dark:border-red-800">
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('permission-registry::Expected fields for continue step') }}</p>
                        <div class="grid grid-cols-1 gap-2 mb-2">
                            @foreach($expectedFields as $field)
                                <div>
                                    <label for="continue_step_{{ $grantedPermissionId }}_{{ $trigger['trigger_id'] }}_{{ $field['name'] }}" class="sr-only">{{ $field['description'] ?? $field['name'] }}</label>
                                    <input type="text"
                                           id="continue_step_{{ $grantedPermissionId }}_{{ $trigger['trigger_id'] }}_{{ $field['name'] }}"
                                           wire:model="continueStepFields.{{ $grantedPermissionId }}.{{ $trigger['trigger_id'] }}.{{ $field['name'] }}"
                                           class="block w-full text-sm rounded border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-gray-100 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="{{ $field['description'] ?? $field['name'] }}{{ !empty($field['required']) ? ' *' : '' }}">
                                </div>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ __('permission-registry::Enter values and click Continue step') }}</p>
                    </div>
                @endif
            </div>
        </div>
        @if($grantedPermissionId && isset($trigger['trigger_id']))
            <div class="mt-3 flex justify-end sm:justify-start">
                <button 
                    type="button"
                    wire:click="retryTrigger({{ $grantedPermissionId }}, {{ $trigger['trigger_id'] }})"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800 shadow-sm min-h-[2.5rem]"
                    aria-label="{{ __('permission-registry::Continue step') }}: {{ $trigger['name'] }}"
                >
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ __('permission-registry::Continue step') }}
                </button>
            </div>
        @endif
    @elseif($trigger['status'] === 'running')
        <div class="flex items-center space-x-2 flex-shrink-0">
            <svg class="animate-spin w-5 h-5 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
        <span class="text-gray-700 dark:text-gray-300">{{ $trigger['name'] }}</span>
        <span class="ml-1 px-1.5 py-0.5 text-xs bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded animate-pulse">
            {{ __('permission-registry::Running') }}
        </span>
        <span class="sr-only" aria-live="polite">{{ $statusLabel }}</span>
    @else
        {{-- pending --}}
        <div class="flex items-center space-x-2 flex-shrink-0">
            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="10" stroke-width="2"/>
            </svg>
        </div>
        <span class="text-gray-600 dark:text-gray-400">{{ $trigger['name'] }}</span>
        <span class="sr-only">{{ $statusLabel }}</span>
    @endif
</div>
