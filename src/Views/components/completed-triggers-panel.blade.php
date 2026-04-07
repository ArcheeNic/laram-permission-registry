@props(['completedPermissionsWithErrors' => [], 'isProcessing' => false])
@if(!$isProcessing && !empty($completedPermissionsWithErrors))
    <div id="trigger-execution-errors" class="mt-6 p-5 bg-red-50 dark:bg-red-900/25 rounded-xl border-2 border-red-300 dark:border-red-700 shadow-md ring-2 ring-red-200/50 dark:ring-red-800/30" role="alert">
        @if(config('app.debug'))
            <div class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                Debug: {{ count($completedPermissionsWithErrors) }} permissions with errors
            </div>
        @endif
        <div class="flex items-center gap-3 mb-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div>
                <h5 class="text-base font-bold text-red-900 dark:text-red-100">{{ __('permission-registry::Trigger Execution Errors') }}</h5>
                <p class="text-xs text-red-700 dark:text-red-300 mt-0.5">{{ __('permission-registry::Enter values below and use Continue step to retry') }}</p>
            </div>
        </div>
        
        @foreach($completedPermissionsWithErrors as $permId => $status)
            <div class="mb-4 last:mb-0 bg-white dark:bg-neutral-800 p-3 rounded border border-red-200 dark:border-red-800">
                <p class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-2">
                    {{ $status['permission_name'] ?? 'Unknown permission' }}
                    <span class="text-xs text-gray-500">(ID: {{ $permId }})</span>
                </p>
                @if(config('app.debug'))
                    {{-- Debug: show all data (only in development) --}}
                    <div class="text-xs text-gray-500 mb-2">
                        Status: {{ $status['status'] ?? 'N/A' }} | 
                        Triggers: {{ isset($status['triggers']) ? count($status['triggers']) : 0 }}
                    </div>
                @endif
                <div class="space-y-2">
                    @if(isset($status['triggers']) && is_array($status['triggers']))
                        @foreach($status['triggers'] as $trigger)
                            <x-pr::trigger-status-item :trigger="$trigger" :grantedPermissionId="$status['granted_permission_id']" />
                        @endforeach
                    @endif
                </div>
                {{-- Кнопка продолжения выдачи или отзыва --}}
                @if(isset($status['granted_permission_id']))
                    @php $eventType = $status['event_type'] ?? 'grant'; @endphp
                    <div class="flex justify-end mt-3 pt-3 border-t border-red-200 dark:border-red-700">
                        @if($eventType === 'revoke')
                            <button 
                                wire:click="continueRevoking({{ $status['granted_permission_id'] }})"
                                class="px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 dark:bg-orange-500 dark:hover:bg-orange-600 rounded-lg transition-colors"
                                aria-label="{{ __('permission-registry::Continue Revoking') }}"
                            >
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ __('permission-registry::Continue Revoking') }}
                            </button>
                        @else
                            <button 
                                wire:click="continueGranting({{ $status['granted_permission_id'] }})"
                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 rounded-lg transition-colors"
                                aria-label="{{ __('permission-registry::Continue Granting') }}"
                            >
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ __('permission-registry::Continue Granting') }}
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif
