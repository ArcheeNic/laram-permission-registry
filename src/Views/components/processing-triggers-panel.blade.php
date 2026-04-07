@props(['processingPermissions' => [], 'isProcessing' => false])

@if($isProcessing && !empty($processingPermissions))
    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
        <div class="flex items-center mb-3">
            <svg class="animate-spin h-5 w-5 text-blue-600 dark:text-blue-400 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <h5 class="font-semibold text-blue-900 dark:text-blue-100">{{ __('permission-registry::Executing Triggers...') }}</h5>
        </div>
        
        @foreach($processingPermissions as $permId => $status)
            <div class="mb-4 last:mb-0">
                <p class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-2">{{ $status['permission_name'] }}</p>
                <div class="space-y-2">
                    @foreach($status['triggers'] as $trigger)
                        <x-pr::trigger-status-item :trigger="$trigger" />
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif
