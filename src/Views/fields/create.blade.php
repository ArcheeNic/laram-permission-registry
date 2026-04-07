<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('permission-registry::Create Permission Field') }}
            </h2>
            <a href="{{ route('permission-registry::fields.index') }}"
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                {{ __('permission-registry::Back to Fields') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <form action="{{ route('permission-registry::fields.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            {{ __('permission-registry::Name') }} *
                            <x-perm::field-hint
                                :title="__('permission-registry::hints.fields_name_title')"
                                :description="__('permission-registry::hints.fields_name_desc')"
                            />
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="default_value" class="block text-sm font-medium text-gray-700">
                            {{ __('permission-registry::Default Value') }}
                            <x-perm::field-hint
                                :title="__('permission-registry::hints.fields_default_value_title')"
                                :description="__('permission-registry::hints.fields_default_value_desc')"
                            />
                        </label>
                        <input type="text" name="default_value" id="default_value" value="{{ old('default_value') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('default_value')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="is_global" id="is_global" value="1" 
                                   {{ old('is_global') ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:bg-neutral-700 dark:border-neutral-600"
                                   onchange="document.getElementById('required_on_user_create_wrapper').style.display = this.checked ? 'block' : 'none';">
                            <label for="is_global" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                {{ __('permission-registry::Global Field') }}
                                <x-perm::field-hint
                                    :title="__('permission-registry::hints.fields_is_global_title')"
                                    :description="__('permission-registry::hints.fields_is_global_desc')"
                                />
                            </label>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('permission-registry::Global fields are shared across all permissions for a user and are always required when granting permissions.') }}
                        </p>
                        @error('is_global')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="required_on_user_create_wrapper" class="mb-6" style="display: {{ old('is_global') ? 'block' : 'none' }};">
                        <div class="flex items-center">
                            <input type="checkbox" name="required_on_user_create" id="required_on_user_create" value="1" 
                                   {{ old('required_on_user_create') ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:bg-neutral-700 dark:border-neutral-600">
                            <label for="required_on_user_create" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                {{ __('permission-registry::Required on User Create') }}
                                <x-perm::field-hint
                                    :title="__('permission-registry::hints.fields_required_on_user_create_title')"
                                    :description="__('permission-registry::hints.fields_required_on_user_create_desc')"
                                />
                            </label>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('permission-registry::This field will be required when creating a new user and used for display name generation.') }}
                        </p>
                        @error('required_on_user_create')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                            {{ __('permission-registry::Create Field') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
