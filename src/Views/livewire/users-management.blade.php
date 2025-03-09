<div>
    <div class="flex flex-col md:flex-row md:space-x-6">
        <!-- Список пользователей -->
        <div class="w-full md:w-1/3 mb-6 md:mb-0">
            <div class="mb-4 flex justify-between items-center">
                <input wire:model.live="search" type="text" placeholder="{{ __('permission-registry::Search users') }}"
                       class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent mr-2">
                <button wire:click="toggleCreateForm" class="px-3 py-2 bg-green-600 text-white rounded-md">
                    <span class="text-xl">+</span>
                </button>
            </div>

            @if($showCreateForm)
                <div class="mb-4 p-4 bg-gray-100 rounded-lg">
                    <h3 class="text-lg font-medium mb-2">{{ __('permission-registry::Create New User') }}</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('permission-registry::Name') }}</label>
                            <input wire:model="newUserName" type="text" class="mt-1 w-full px-3 py-2 rounded-md border border-gray-300">
                            @error('newUserName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('permission-registry::Email') }}</label>
                            <input wire:model="newUserEmail" type="email" class="mt-1 w-full px-3 py-2 rounded-md border border-gray-300">
                            @error('newUserEmail') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex justify-end space-x-2">
                            <button wire:click="toggleCreateForm" class="px-3 py-1 border border-gray-300 rounded-md">
                                {{ __('permission-registry::Cancel') }}
                            </button>
                            <button wire:click="createUser" class="px-3 py-1 bg-blue-600 text-white rounded-md">
                                {{ __('permission-registry::Create') }}
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="divide-y divide-gray-200">
                    @foreach($users as $user)
                        <div wire:click="selectUser({{ $user->id }})"
                             class="p-4 cursor-pointer hover:bg-gray-50 transition-colors {{ $selectedUserId === $user->id ? 'bg-blue-50' : '' }}">
                            <div class="font-medium">{{ $user->name }}</div>
                            <div class="text-sm text-gray-500">{{ $user->email }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="p-4 border-t border-gray-200">
                    {{ $users->links() }}
                </div>
            </div>
        </div>

        <!-- Информация о пользователе и управление -->
        <div class="w-full md:w-2/3">
            @if($selectedUser)
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold">{{ $selectedUser->name }}</h3>
                        <p class="text-gray-600">{{ $selectedUser->email }}</p>
                    </div>

                    <!-- Назначение должности -->
                    <div class="mb-6">
                        <h4 class="text-md font-semibold mb-3">{{ __('permission-registry::Positions') }}</h4>

                        <div class="mb-3">
                            @if($selectedUser->positions->isNotEmpty())
                                <div class="flex flex-wrap gap-2 mb-4">
                                    @foreach($selectedUser->positions as $position)
                                        <div class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                            <span>{{ $position->name }}</span>
                                            <button wire:click="removePosition({{ $position->id }})"
                                                    class="ml-2 text-blue-500 hover:text-blue-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 mb-4">{{ __('permission-registry::No positions assigned') }}</p>
                            @endif
                        </div>

                        <div class="flex items-end space-x-3">
                            <div class="flex-grow">
                                <label for="position" class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('permission-registry::Add Position') }}
                                </label>
                                <select id="position" wire:model.live="selectedPosition"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="">{{ __('permission-registry::Select a position') }}</option>
                                    @foreach($this->positions as $position)
                                        <option value="{{ $position->id }}">{{ $position->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button wire:click="assignPosition"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                    @if(!$selectedPosition) disabled @endif>
                                {{ __('permission-registry::Add') }}
                            </button>
                        </div>
                    </div>

                    <!-- Управление группами -->
                    <div>
                        <h4 class="text-md font-semibold mb-3">{{ __('permission-registry::Groups') }}</h4>

                        <div class="mb-3">
                            @if($selectedUser->groups->isNotEmpty())
                                <div class="flex flex-wrap gap-2">
                                    @foreach($selectedUser->groups as $group)
                                        <div class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                            <span>{{ $group->name }}</span>
                                            <button wire:click="removeGroup({{ $group->id }})"
                                                    class="ml-1 text-blue-500 hover:text-blue-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500">{{ __('permission-registry::No groups assigned') }}</p>
                            @endif
                        </div>

                        <!-- Permissions Management -->
                        <div class="mt-6">
                            <h4 class="text-md font-semibold mb-3">{{ __('permission-registry::Permissions Management') }}</h4>

                            <!-- 1. Зависимые права -->
                            <div class="mb-6">
                                <h5 class="text-sm font-semibold mb-2 text-gray-700">{{ __('permission-registry::Dependent Permissions (from Positions and Groups)') }}</h5>
                                <div class="bg-gray-50 p-4 rounded-md">
                                    @if($this->dependentPermissions && $this->dependentPermissions->count() > 0)
                                        <div class="max-h-64 overflow-y-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                <tr>
                                                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">{{ __('permission-registry::Active') }}</th>
                                                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('permission-registry::Service') }}</th>
                                                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('permission-registry::Name') }}</th>
                                                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('permission-registry::Source') }}</th>
                                                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">{{ __('permission-registry::Fields') }}</th>
                                                </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                @foreach($this->dependentPermissions as $permission)
                                                    <tr class="hover:bg-gray-50 transition-colors">
                                                        <td class="px-3 py-2 whitespace-nowrap">
                                                            <input type="checkbox" id="dependent_permission_{{ $permission['id'] }}"
                                                                   wire:model.live="dependentSelectedPermissions.{{ $permission['id'] }}"
                                                                   class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                                        </td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500">{{ $permission['service'] }}</td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-xs">
                                                            <label for="dependent_permission_{{ $permission['id'] }}" class="font-medium text-gray-700 cursor-pointer">
                                                                {{ $permission['name'] }}
                                                            </label>
                                                            @if($permission['description'])
                                                                <p class="text-xs text-gray-500 mt-1">{{ $permission['description'] }}</p>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500">
                                                            @if($permission['source_type'] === 'position')
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $permission['source_name'] }} ({{ __('permission-registry::Position') }})
                                        </span>
                                                            @else
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            {{ $permission['source_name'] }} ({{ __('permission-registry::Group') }})
                                        </span>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-2 whitespace-nowrap">
                                                            @if(isset($permission['has_fields']) && $permission['has_fields'] &&
                                                               isset($dependentSelectedPermissions[$permission['id']]) &&
                                                               $dependentSelectedPermissions[$permission['id']])
                                                                <button wire:click="toggleDependentPermissionFields({{ $permission['id'] }})"
                                                                        class="text-blue-600 hover:text-blue-800 text-xs focus:outline-none">
                                                                    @if(isset($expandedDependentPermissionFields[$permission['id']]))
                                                                        {{ __('permission-registry::Hide') }}
                                                                    @else
                                                                        {{ __('permission-registry::Show') }}
                                                                    @endif
                                                                </button>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <!-- Поля доступа - отображаются только если разрешено -->
                                                    @if(isset($permission['has_fields']) &&
                                                        $permission['has_fields'] &&
                                                        isset($dependentSelectedPermissions[$permission['id']]) &&
                                                        $dependentSelectedPermissions[$permission['id']] &&
                                                        isset($expandedDependentPermissionFields[$permission['id']]))
                                                        <tr class="bg-blue-50">
                                                            <td colspan="5" class="px-3 py-2">
                                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-2">
                                                                    @foreach($permission['fields'] as $field)
                                                                        <div class="flex flex-col">
                                                                            <label for="dependent_field_{{ $permission['id'] }}_{{ $field['id'] }}" class="text-xs font-medium text-gray-600">
                                                                                {{ $field['name'] }}:
                                                                            </label>
                                                                            <input type="text" id="dependent_field_{{ $permission['id'] }}_{{ $field['id'] }}"
                                                                                   wire:model="dependentPermissionFields.{{ $permission['id'] }}.{{ $field['id'] }}"
                                                                                   class="mt-1 text-sm rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                                                   placeholder="{{ $field['default_value'] }}">
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-500">{{ __('permission-registry::No dependent permissions found') }}</p>
                                    @endif
                                </div>
                            </div>

                            <!-- 2. Прямые права -->
                            <div>
                                <h5 class="text-sm font-semibold mb-2 text-gray-700">{{ __('permission-registry::Direct Permissions') }}</h5>
                                <div class="bg-gray-50 p-4 rounded-md">
                                    @if($this->availablePermissions && $this->availablePermissions->count() > 0)
                                        <div class="mb-4">
                                            <input type="text" wire:model.live="permissionSearch" placeholder="{{ __('permission-registry::Search permissions') }}"
                                                   class="text-sm rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 w-full">
                                        </div>

                                        <div class="max-h-96 overflow-y-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                <tr>
                                                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">{{ __('permission-registry::Active') }}</th>
                                                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('permission-registry::Service') }}</th>
                                                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('permission-registry::Name') }}</th>
                                                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">{{ __('permission-registry::Fields') }}</th>
                                                </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                @foreach($this->availablePermissions as $permission)
                                                    <tr class="hover:bg-gray-50 transition-colors">
                                                        <td class="px-3 py-2 whitespace-nowrap">
                                                            <input type="checkbox" id="permission_{{ $permission->id }}"
                                                                   wire:model.live="selectedPermissions.{{ $permission->id }}"
                                                                   class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                                        </td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500">{{ $permission->service }}</td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-xs">
                                                            <label for="permission_{{ $permission->id }}" class="font-medium text-gray-700 cursor-pointer">
                                                                {{ $permission->name }}
                                                            </label>
                                                            @if($permission->description)
                                                                <p class="text-xs text-gray-500 mt-1">{{ $permission->description }}</p>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-2 whitespace-nowrap">
                                                            @if($permission->fields->count() > 0 && isset($selectedPermissions[$permission->id]) && $selectedPermissions[$permission->id])
                                                                <button wire:click="togglePermissionFields({{ $permission->id }})"
                                                                        class="text-blue-600 hover:text-blue-800 text-xs focus:outline-none">
                                                                    @if(isset($expandedPermissionFields[$permission->id]))
                                                                        {{ __('permission-registry::Hide') }}
                                                                    @else
                                                                        {{ __('permission-registry::Show') }}
                                                                    @endif
                                                                </button>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <!-- Поля доступа - отображаются только если разрешено -->
                                                    @if($permission->fields->count() > 0 &&
                                                        isset($selectedPermissions[$permission->id]) &&
                                                        $selectedPermissions[$permission->id] &&
                                                        isset($expandedPermissionFields[$permission->id]))
                                                        <tr class="bg-blue-50">
                                                            <td colspan="4" class="px-3 py-2">
                                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-2">
                                                                    @foreach($permission->fields as $field)
                                                                        <div class="flex flex-col">
                                                                            <label for="field_{{ $permission->id }}_{{ $field->id }}" class="text-xs font-medium text-gray-600">
                                                                                {{ $field->name }}:
                                                                            </label>
                                                                            <input type="text" id="field_{{ $permission->id }}_{{ $field->id }}"
                                                                                   wire:model="permissionFields.{{ $permission->id }}.{{ $field->id }}"
                                                                                   class="mt-1 text-sm rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                                                   placeholder="{{ $field->default_value }}">
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="mt-4 flex justify-end">
                                            <button wire:click="saveUserPermissions"
                                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                                                {{ __('permission-registry::Save Permissions') }}
                                            </button>
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-500">{{ __('permission-registry::No permissions available') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-end space-x-3">
                            <div class="flex-grow">
                                <label for="group" class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('permission-registry::Assign Group') }}
                                </label>
                                <select id="group" wire:model.live="selectedGroup"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="">{{ __('permission-registry::Select a group') }}</option>
                                    @foreach($this->groups as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button wire:click="assignGroup"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                    @if(!$selectedGroup) disabled @endif>
                                {{ __('permission-registry::Assign') }}
                            </button>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white rounded-lg border border-gray-200 p-6 flex items-center justify-center h-full">
                    <p class="text-gray-500">{{ __('permission-registry::Select a user to manage permissions') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
