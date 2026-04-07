<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                🔗 {{ __('permission-registry::Dependencies') }}: {{ $permission->name }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('permission-registry::permissions.edit', $permission) }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-neutral-700 dark:border-neutral-600 dark:hover:bg-neutral-600">
                    {{ __('permission-registry::Back to Edit') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @livewire('permission-registry::permission-dependencies', ['permission' => $permission])
        </div>
    </div>
</x-app-layout>
