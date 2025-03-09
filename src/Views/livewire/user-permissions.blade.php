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
