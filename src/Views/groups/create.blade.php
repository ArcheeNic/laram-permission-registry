<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('permission-registry::Create Group') }}
            </h2>
            <a href="{{ route('permission-registry::groups.index') }}"
               class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-neutral-800 border border-gray-300 dark:border-neutral-600 rounded-md hover:bg-gray-50 dark:hover:bg-neutral-800">
                {{ __('permission-registry::Back to Groups') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                <form action="{{ route('permission-registry::groups.store') }}" method="POST">
                    @csrf

                    <div class="mb-6">
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('permission-registry::Name') }} *
                            <x-perm::field-hint
                                :title="__('permission-registry::hints.groups_name_title')"
                                :description="__('permission-registry::hints.groups_name_desc')"
                            />
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-neutral-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('permission-registry::Description') }}
                            <x-perm::field-hint
                                :title="__('permission-registry::hints.groups_description_title')"
                                :description="__('permission-registry::hints.groups_description_desc')"
                            />
                        </label>
                        <textarea name="description" id="description" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-neutral-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
                        @error('description')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('permission-registry::Permissions') }}
                            <x-perm::field-hint
                                :title="__('permission-registry::hints.groups_permissions_title')"
                                :description="__('permission-registry::hints.groups_permissions_desc')"
                            />
                        </label>
                        <div class="bg-gray-50 dark:bg-neutral-800 p-4 rounded-md max-h-96 overflow-y-auto">
                            @if($permissions->isEmpty())
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('permission-registry::No permissions available. Create permissions first.') }}</p>
                            @else
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach($permissions as $permission)
                                        @php
                                            $isResourceScoped = ($permission->scope ?? \ArcheeNic\PermissionRegistry\Enums\PermissionScope::Service) === \ArcheeNic\PermissionRegistry\Enums\PermissionScope::Resource;
                                            $permissionChecked = in_array($permission->id, old('permissions', []));
                                            $selectedForPermission = old("permission_resources.{$permission->id}", []);
                                        @endphp
                                        <div class="flex flex-col">
                                            <div class="flex items-start">
                                                <div class="flex items-center h-5">
                                                    <input type="checkbox" id="permission_{{ $permission->id }}" name="permissions[]" value="{{ $permission->id }}"
                                                           class="h-4 w-4 text-blue-600 dark:text-blue-300 border-gray-300 dark:border-neutral-600 rounded focus:ring-blue-500 permission-checkbox"
                                                           data-permission-id="{{ $permission->id }}"
                                                        {{ $permissionChecked ? 'checked' : '' }}>
                                                </div>
                                                <div class="ml-3 text-sm">
                                                    <label for="permission_{{ $permission->id }}" class="font-medium text-gray-700 dark:text-gray-300">
                                                        {{ $permission->name }}
                                                    </label>
                                                    <p class="text-gray-500 dark:text-gray-400">{{ $permission->service }}</p>
                                                    @if($permission->description)
                                                        <p class="text-gray-500 dark:text-gray-400 text-xs">{{ Str::limit($permission->description, 100) }}</p>
                                                    @endif
                                                </div>
                                            </div>

                                            @if($isResourceScoped)
                                                <div class="ml-7 mt-2 resource-block" data-permission-id="{{ $permission->id }}" style="{{ $permissionChecked ? '' : 'display:none' }}">
                                                    <p class="text-xs text-gray-700 dark:text-gray-300 mb-1">
                                                        {{ __('permission-registry::Resources') }}
                                                        @if($permission->resource_kind)
                                                            <span class="text-gray-400 dark:text-gray-500">({{ $permission->resource_kind }})</span>
                                                        @endif
                                                    </p>
                                                    @php $resources = $resourceCatalog[$permission->id] ?? collect(); @endphp
                                                    @if($resources->isEmpty())
                                                        <p class="text-xs text-amber-600 dark:text-amber-400">
                                                            {{ __('permission-registry::No resources discovered yet — run sync first') }}
                                                        </p>
                                                    @else
                                                        <div class="max-h-48 overflow-y-auto border border-gray-200 dark:border-neutral-600 rounded p-2 bg-white dark:bg-neutral-800">
                                                            @foreach($resources as $resource)
                                                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                                                    <input type="checkbox"
                                                                           name="permission_resources[{{ $permission->id }}][]"
                                                                           value="{{ $resource->id }}"
                                                                           class="h-4 w-4 text-blue-600 dark:text-blue-300 border-gray-300 dark:border-neutral-500 rounded focus:ring-blue-500"
                                                                        {{ in_array($resource->id, $selectedForPermission) ? 'checked' : '' }}>
                                                                    <span>{{ $resource->name }}</span>
                                                                    <span class="ml-auto text-xs font-mono text-gray-400 dark:text-gray-500">{{ $resource->external_id }}</span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @error('permissions')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                            {{ __('permission-registry::Create Group') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.permission-checkbox');

            function toggleResourceBlock(checkbox) {
                const permissionId = checkbox.getAttribute('data-permission-id');
                const block = document.querySelector('.resource-block[data-permission-id="' + permissionId + '"]');
                if (!block) return;
                block.style.display = checkbox.checked ? '' : 'none';
                if (!checkbox.checked) {
                    block.querySelectorAll('input[type="checkbox"]').forEach(function (cb) { cb.checked = false; });
                }
            }

            checkboxes.forEach(function (cb) {
                cb.addEventListener('change', function () { toggleResourceBlock(cb); });
            });
        });
    </script>
</x-app-layout>
