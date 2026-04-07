@props(['processed', 'total'])

<div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border-2 border-blue-200 dark:border-blue-800 shadow-sm"
     x-data
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform -translate-y-2"
     x-transition:enter-end="opacity-100 transform translate-y-0">
    <div class="flex items-center space-x-3">
        <svg class="animate-spin h-6 w-6 text-blue-600 dark:text-blue-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <div class="flex-1">
            <h5 class="font-semibold text-blue-900 dark:text-blue-100">{{ __('permission-registry::Processing permissions') }}</h5>
            <div class="flex items-center space-x-4 mt-2">
                <span class="text-sm text-blue-800 dark:text-blue-200">
                    {{ __('permission-registry::Processed') }}: {{ $processed }} {{ __('permission-registry::of') }} {{ $total }}
                </span>
                <div class="flex-1 bg-blue-200 dark:bg-blue-800 rounded-full h-2 overflow-hidden">
                    <div class="bg-blue-600 dark:bg-blue-400 h-2 rounded-full transition-all duration-500 ease-out"
                         style="width: {{ $total > 0 ? round(($processed / $total) * 100) : 0 }}%"></div>
                </div>
                <span class="text-sm font-medium text-blue-800 dark:text-blue-200">
                    {{ $total > 0 ? round(($processed / $total) * 100) : 0 }}%
                </span>
            </div>
        </div>
    </div>
</div>
