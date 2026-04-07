<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('permission-registry::Users Management') }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="w-full px-2 sm:px-4 lg:px-6">
            <livewire:permission-registry::users-management />
        </div>
    </div>
</x-app-layout>
