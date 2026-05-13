<div>
    <div class="mb-4 flex flex-col sm:flex-row justify-between gap-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <input wire:model.live="search" type="text" placeholder="{{ __('permission-registry::Search fields') }}"
                   class="rounded-md shadow-sm border-gray-300 dark:border-neutral-600 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
        </div>

        <div class="flex items-center gap-2">
            <span>{{ __('permission-registry::Show') }}:</span>
            <select wire:model.live="perPage" class="rounded-md shadow-sm border-gray-300 dark:border-neutral-600 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 bg-green-100 dark:bg-green-900/30 border-l-4 border-green-500 dark:border-green-400 text-green-700 dark:text-green-300 p-4" role="alert">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-sm sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
            <thead class="bg-gray-50 dark:bg-neutral-800">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    {{ __('permission-registry::Name') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    {{ __('permission-registry::Default Value') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    {{ __('permission-registry::Actions') }}
                </th>
            </tr>
            </thead>
            <tbody class="bg-white dark:bg-neutral-800 divide-y divide-gray-200 dark:divide-neutral-700">
            @foreach($fields as $field)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ $field->name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $field->default_value ?: '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('permission-registry::fields.edit', $field) }}"
                           class="text-indigo-600 dark:text-indigo-300 hover:text-indigo-900 mr-3">{{ __('permission-registry::Edit') }}</a>
                        <button wire:click="confirmDelete({{ $field->id }})"
                                class="text-red-600 dark:text-red-300 hover:text-red-900">{{ __('permission-registry::Delete') }}</button>
                    </td>
                </tr>
            @endforeach

            @if($fields->count() === 0)
                <tr>
                    <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ __('permission-registry::No fields found') }}
                    </td>
                </tr>
            @endif
            </tbody>
        </table>

        <div class="p-4">
            {{ $fields->links() }}
        </div>
    </div>

    <!-- Модальное окно подтверждения удаления -->
    @if($confirmingDelete)
        <div class="fixed inset-0 bg-gray-500 dark:bg-gray-700 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-neutral-800 rounded-lg overflow-hidden shadow-xl max-w-md w-full">
                <div class="px-6 py-4">
                    <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ __('permission-registry::Confirm Delete') }}
                    </div>
                    <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('permission-registry::Are you sure you want to delete this field? This action cannot be undone.') }}
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-100 dark:bg-neutral-900 flex justify-end space-x-3">
                    <button wire:click="cancelDelete" type="button"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-neutral-800 border border-gray-300 dark:border-neutral-600 rounded-md hover:bg-gray-50 dark:hover:bg-neutral-800">
                        {{ __('permission-registry::Cancel') }}
                    </button>
                    <button wire:click="deleteField" type="button"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">
                        {{ __('permission-registry::Delete') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
