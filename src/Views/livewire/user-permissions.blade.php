<!-- Permissions Management -->
<div class="mt-6">
    <h4 class="text-md font-semibold mb-3">{{ __('permission-registry::Permissions Management') }}</h4>

    <div class="bg-gray-50 p-4 rounded-md">
        @if($this->availablePermissions && $this->availablePermissions->count() > 0)
            <div class="mb-4">
                <input type="text" wire:model.live="permissionSearch" placeholder="{{ __('permission-registry::Search permissions') }}"
                       class="text-sm rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 w-full">
            </div>

            <div class="max-h-96 overflow-y-auto">
                @foreach($this->availablePermissions as $permission)
                    <div class="mb-4 p-3 border border-gray-200 rounded-md bg-white permission-item" data-name="{{ strtolower($permission->name) }}" data-service="{{ strtolower($permission->service) }}">
                        <div class="flex items-start">
                            <div class="flex items-center h-5 mt-1">
                                <input type="checkbox" id="permission_{{ $permission->id }}"
                                       wire:model.live="selectedPermissions.{{ $permission->id }}"
                                       class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </div>
                            <div class="ml-3 flex-grow">
                                <label for="permission_{{ $permission->id }}" class="font-medium text-gray-700">
                                    {{ $permission->name }}
                                </label>
                                <p class="text-xs text-gray-500">{{ $permission->service }}</p>
                                <p class="text-xs text-gray-500">{{ $permission->description }}</p>

                                @if($permission->fields->count() > 0 && isset($selectedPermissions[$permission->id]) && $selectedPermissions[$permission->id])
                                    <div class="mt-2 border-t border-gray-100 pt-2">
                                        <p class="text-xs text-gray-700 mb-1">{{ __('permission-registry::Permission fields') }}:</p>
                                        <div class="grid grid-cols-1 gap-2">
                                            @foreach($permission->fields as $field)
                                                <div class="flex flex-col">
                                                    <label for="field_{{ $permission->id }}_{{ $field->id }}" class="text-xs text-gray-600">
                                                        {{ $field->name }}:
                                                    </label>
                                                    <input type="text" id="field_{{ $permission->id }}_{{ $field->id }}"
                                                           wire:model="permissionFields.{{ $permission->id }}.{{ $field->id }}"
                                                           class="mt-1 text-sm rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                           placeholder="{{ $field->default_value }}">
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 flex justify-end">
                <button wire:click="saveUserPermissions"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                    {{ __('permission-registry::Save Permissions') }}
                </button>
            </div>
        @else
            <p class="text-gray-500">{{ __('permission-registry::No permissions available') }}</p>
        @endif
    </div>
</div>

<!-- Modal for missing global fields -->
@if($showGlobalFieldsModal)
<div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 dark:bg-neutral-900 dark:bg-opacity-75" aria-hidden="true"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white dark:bg-neutral-800 rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="px-4 pt-5 pb-4 bg-white dark:bg-neutral-800 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-blue-100 dark:bg-blue-900 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100" id="modal-title">
                            {{ __('permission-registry::Fill Required Global Fields') }}
                        </h3>
                        <div class="mt-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                {{ __('permission-registry::Please fill the following required global fields before granting this permission:') }}
                            </p>
                            
                            @foreach($missingGlobalFields as $field)
                            <div class="mb-4">
                                <label for="global_field_{{ $field['id'] }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $field['name'] }} *
                                </label>
                                <input type="text" 
                                       id="global_field_{{ $field['id'] }}"
                                       wire:model="fieldValues.{{ $field['id'] }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-4 py-3 bg-gray-50 dark:bg-neutral-700 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" 
                        wire:click="saveGlobalFieldsAndGrant"
                        class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                    {{ __('permission-registry::Save and Grant') }}
                </button>
                <button type="button" 
                        wire:click="closeGlobalFieldsModal"
                        class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-neutral-600 border border-gray-300 dark:border-neutral-500 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-neutral-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                    {{ __('permission-registry::Cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif
