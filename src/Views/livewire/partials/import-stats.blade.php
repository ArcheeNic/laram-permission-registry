<div class="p-4 border-b dark:border-neutral-700 grid grid-cols-2 sm:grid-cols-5 gap-3">
    <div class="text-center p-2 rounded bg-gray-50 dark:bg-neutral-700">
        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stagingStats['total'] }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('permission-registry::messages.import.total') }}</p>
    </div>
    <div class="text-center p-2 rounded bg-green-50 dark:bg-green-900/20">
        <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $stagingStats['new'] }}</p>
        <p class="text-xs text-green-600 dark:text-green-400">{{ __('permission-registry::messages.import.status_new') }}</p>
        <p class="mt-0.5 text-xs text-green-500 dark:text-green-500 opacity-75">{{ __('permission-registry::messages.import.hint_new') }}</p>
    </div>
    <div class="text-center p-2 rounded bg-yellow-50 dark:bg-yellow-900/20">
        <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">{{ $stagingStats['changed'] }}</p>
        <p class="text-xs text-yellow-600 dark:text-yellow-400">{{ __('permission-registry::messages.import.status_changed') }}</p>
        <p class="mt-0.5 text-xs text-yellow-500 dark:text-yellow-500 opacity-75">{{ __('permission-registry::messages.import.hint_changed') }}</p>
    </div>
    <div class="text-center p-2 rounded bg-gray-50 dark:bg-neutral-700">
        <p class="text-2xl font-bold text-gray-500 dark:text-gray-400">{{ $stagingStats['exists'] }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('permission-registry::messages.import.status_exists') }}</p>
        <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500 opacity-75">{{ __('permission-registry::messages.import.hint_exists') }}</p>
    </div>
    <div class="text-center p-2 rounded bg-red-50 dark:bg-red-900/20">
        <p class="text-2xl font-bold text-red-700 dark:text-red-300">{{ $stagingStats['missing'] }}</p>
        <p class="text-xs text-red-600 dark:text-red-400">{{ __('permission-registry::messages.import.status_missing') }}</p>
        <p class="mt-0.5 text-xs text-red-500 dark:text-red-500 opacity-75">{{ __('permission-registry::messages.import.hint_missing') }}</p>
    </div>
</div>
