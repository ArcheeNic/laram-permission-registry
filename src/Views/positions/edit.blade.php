<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('permission-registry::Edit Position') }}: {{ $position->name }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('permission-registry::positions.show', $position) }}"
                   class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                    {{ __('permission-registry::View') }}
                </a>
                <a href="{{ route('permission-registry::positions.index') }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    {{ __('permission-registry::Back to Positions') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <form action="{{ route('permission-registry::positions.update', $position) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-6">
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            {{ __('permission-registry::Name') }} *
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name', $position->name) }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="description" class="block text-sm font-medium text-gray-700">
                            {{ __('permission-registry::Description') }}
                        </label>
                        <textarea name="description" id="description" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $position->description) }}</textarea>
                        @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="parent_id" class="block text-sm font-medium text-gray-700">
                            {{ __('permission-registry::Parent Position') }}
                        </label>
                        <select name="parent_id" id="parent_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">{{ __('permission-registry::None (Root Position)') }}</option>
                            @foreach($positions as $parentPosition)
                                <option value="{{ $parentPosition->id }}" {{ old('parent_id', $position->parent_id) == $parentPosition->id ? 'selected' : '' }}>
                                    {{ $parentPosition->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('parent_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('permission-registry::Permissions') }}
                        </label>
                        <div class="bg-gray-50 p-4 rounded-md max-h-96 overflow-y-auto">
                            @if($permissions->isEmpty())
                                <p class="text-sm text-gray-500">{{ __('permission-registry::No permissions available. Create permissions first.') }}</p>
                            @else
                                <div class="mb-2 flex justify-between items-center">
                                    <div>
                                        <input type="text" id="permissionSearch" placeholder="{{ __('permission-registry::Search permissions') }}"
                                               class="text-sm rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <button type="button" id="selectAll" class="text-sm text-blue-600 hover:underline">{{ __('permission-registry::Select All') }}</button>
                                        <button type="button" id="deselectAll" class="ml-2 text-sm text-blue-600 hover:underline">{{ __('permission-registry::Deselect All') }}</button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="permissionsList">
                                    @php
                                        $positionPermissionIds = $position->permissions->pluck('id')->toArray();
                                    @endphp
                                    @foreach($permissions as $permission)
                                        <div class="flex items-start permission-item" data-name="{{ strtolower($permission->name) }}" data-service="{{ strtolower($permission->service) }}">
                                            <div class="flex items-center h-5">
                                                <input type="checkbox" id="permission_{{ $permission->id }}" name="permissions[]" value="{{ $permission->id }}"
                                                       class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 permission-checkbox"
                                                    {{ in_array($permission->id, old('permissions', $positionPermissionIds)) ? 'checked' : '' }}>
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label for="permission_{{ $permission->id }}" class="font-medium text-gray-700">
                                                    {{ $permission->name }}
                                                </label>
                                                <p class="text-gray-500">{{ $permission->service }}</p>
                                                @if($permission->description)
                                                    <p class="text-gray-500 text-xs">{{ Str::limit($permission->description, 100) }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @error('permissions')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('permission-registry::Permission Groups') }}
                        </label>
                        <div class="bg-gray-50 p-4 rounded-md">
                            @if($groups->isEmpty())
                                <p class="text-sm text-gray-500">{{ __('permission-registry::No groups available. Create groups first.') }}</p>
                            @else
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @php
                                        $positionGroupIds = $position->groups->pluck('id')->toArray();
                                    @endphp
                                    @foreach($groups as $group)
                                        <div class="flex items-start">
                                            <div class="flex items-center h-5">
                                                <input type="checkbox" id="group_{{ $group->id }}" name="groups[]" value="{{ $group->id }}"
                                                       class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                    {{ in_array($group->id, old('groups', $positionGroupIds)) ? 'checked' : '' }}>
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label for="group_{{ $group->id }}" class="font-medium text-gray-700">
                                                    {{ $group->name }}
                                                </label>
                                                @if($group->description)
                                                    <p class="text-gray-500 text-xs">{{ Str::limit($group->description, 100) }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @error('groups')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                            {{ __('permission-registry::Update Position') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const permissionSearch = document.getElementById('permissionSearch');
            const permissionItems = document.querySelectorAll('.permission-item');
            const selectAllBtn = document.getElementById('selectAll');
            const deselectAllBtn = document.getElementById('deselectAll');
            const checkboxes = document.querySelectorAll('.permission-checkbox');

            // Поиск разрешений
            permissionSearch.addEventListener('input', function() {
                const searchText = this.value.toLowerCase();

                permissionItems.forEach(function(item) {
                    const name = item.getAttribute('data-name');
                    const service = item.getAttribute('data-service');

                    if (name.includes(searchText) || service.includes(searchText)) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });

            // Выбрать все
            selectAllBtn.addEventListener('click', function() {
                checkboxes.forEach(function(checkbox) {
                    if (checkbox.closest('.permission-item').style.display !== 'none') {
                        checkbox.checked = true;
                    }
                });
            });

            // Снять все
            deselectAllBtn.addEventListener('click', function() {
                checkboxes.forEach(function(checkbox) {
                    if (checkbox.closest('.permission-item').style.display !== 'none') {
                        checkbox.checked = false;
                    }
                });
            });
        });
    </script>
</x-app-layout>
