@props([
    'permissions' => [],
    'selectedPermissions' => [],
    'permissionFields' => [],
    'expandedPermissionFields' => [],
    'permissionType' => 'direct',
    'showSource' => false,
    'permissionStatuses' => [],
    'dependentPermissionErrors' => [],
    'showContinueStepLink' => false
])

<div class="max-h-64 overflow-y-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
        <tr>
            <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">{{ __('permission-registry::Active') }}</th>
            <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('permission-registry::Service') }}</th>
            <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('permission-registry::Name') }}</th>
            @if($showSource)
                <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('permission-registry::Source') }}</th>
            @endif
            <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">{{ __('permission-registry::Fields') }}</th>
        </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
        @foreach($permissions as $permission)
            @php
                $permId = is_array($permission) ? $permission['id'] : $permission->id;
                $permService = is_array($permission) ? $permission['service'] : $permission->service;
                $permName = is_array($permission) ? $permission['name'] : $permission->name;
                $permDescription = is_array($permission) ? ($permission['description'] ?? null) : $permission->description;
                $hasFields = is_array($permission) 
                    ? ($permission['has_fields'] ?? false) 
                    : $permission->fields->count() > 0;
                $permFields = is_array($permission) 
                    ? ($permission['fields'] ?? []) 
                    : $permission->fields;
                $inputPrefix = $permissionType === 'dependent' ? 'dependent_permission' : 'permission';
                $statusKey = is_array($permission) ? $permId : $permId;
                $status = is_array($permission) 
                    ? ($permission['status'] ?? null)
                    : ($permissionStatuses[$permId]['status'] ?? null);
                $statusMessage = is_array($permission)
                    ? ($permission['status_message'] ?? null)
                    : ($permissionStatuses[$permId]['status_message'] ?? null);
                $dependentError = $dependentPermissionErrors[$permId] ?? null;
            @endphp
            <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700 transition-colors">
                <td class="px-3 py-2 whitespace-nowrap">
                    <input type="checkbox" id="{{ $inputPrefix }}_{{ $permId }}"
                           wire:model.live="{{ $permissionType === 'dependent' ? 'dependentSelectedPermissions' : 'selectedPermissions' }}.{{ $permId }}"
                           class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                </td>
                <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">{{ $permService }}</td>
                <td class="px-3 py-2 text-xs">
                    <div class="flex items-center gap-2 flex-wrap">
                        <label for="{{ $inputPrefix }}_{{ $permId }}" class="font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                            {{ $permName }}
                        </label>
                        <x-pr::permission-status-badge :status="$status" :statusMessage="$statusMessage" />
                    </div>
                    @if($permDescription)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $permDescription }}</p>
                    @endif
                    @if($status && in_array($status, ['failed', 'partially_granted']) && !empty($statusMessage))
                        <div class="mt-2 p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded text-xs text-red-700 dark:text-red-300">
                            <span class="font-medium">{{ __('permission-registry::Error') }}:</span> {{ $statusMessage }}
                        </div>
                        @if(str_contains($statusMessage, __('permission-registry::fill fields')) || str_contains($statusMessage, 'заполнить поля'))
                            <div class="mt-2">
                                <button type="button"
                                        wire:click="openManualGrantModal({{ $permId }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-amber-600 hover:bg-amber-700 dark:bg-amber-500 dark:hover:bg-amber-600 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    {{ __('permission-registry::Manual Grant') }}
                                </button>
                            </div>
                        @endif
                    @endif
                    @if($dependentError)
                        <div class="mt-2 p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded text-xs text-red-700 dark:text-red-300">
                            <span class="font-medium">{{ __('permission-registry::Error') }}:</span> {{ $dependentError['message'] }}
                        </div>
                    @endif
                    @if($showContinueStepLink && $permissionType === 'dependent' && $status && in_array($status, ['failed', 'partially_granted']))
                        <div class="mt-2">
                            <a href="#trigger-execution-errors" class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 focus:outline-none focus:underline">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ __('permission-registry::Continue step') }}
                            </a>
                        </div>
                    @endif
                </td>
                @if($showSource && is_array($permission))
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
                @endif
                <td class="px-3 py-2 whitespace-nowrap">
                    @if($hasFields && isset($selectedPermissions[$permId]) && $selectedPermissions[$permId])
                        <button wire:click="{{ $permissionType === 'dependent' ? 'toggleDependentPermissionFields' : 'togglePermissionFields' }}({{ $permId }})"
                                class="text-blue-600 hover:text-blue-800 text-xs focus:outline-none">
                            @if(isset($expandedPermissionFields[$permId]))
                                {{ __('permission-registry::Hide') }}
                            @else
                                {{ __('permission-registry::Show') }}
                            @endif
                        </button>
                    @endif
                    @if($dependentError && !empty($dependentError['missing_fields']))
                        <span class="text-amber-600 dark:text-amber-400 text-xs font-medium">{{ __('permission-registry::Fill below') }}</span>
                    @endif
                </td>
            </tr>
            <!-- Недостающие поля при ошибке валидации (заполнить и нажать Сохранить) -->
            @if($permissionType === 'dependent' && $dependentError && !empty($dependentError['missing_fields']))
                <tr class="bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-400 dark:border-amber-600">
                    <td colspan="{{ $showSource ? 5 : 4 }}" class="px-3 py-3">
                        <p class="text-xs font-medium text-amber-800 dark:text-amber-200 mb-2">{{ __('permission-registry::Expected fields for continue step') }} — {{ __('permission-registry::Fill and save') }}</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach($dependentError['missing_fields'] as $missingField)
                                @php
                                    $fieldId = $missingField['id'] ?? 0;
                                    $fieldName = $missingField['name'] ?? '';
                                @endphp
                                <div>
                                    <label for="missing_field_{{ $permId }}_{{ $fieldId }}" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $fieldName }}</label>
                                    <input type="text"
                                           id="missing_field_{{ $permId }}_{{ $fieldId }}"
                                           wire:model="dependentPermissionFields.{{ $permId }}.{{ $fieldId }}"
                                           class="block w-full text-sm rounded-md border-amber-300 dark:border-amber-600 bg-white dark:bg-neutral-700 dark:text-gray-100 shadow-sm focus:ring-amber-500 focus:border-amber-500"
                                           placeholder="{{ $fieldName }}">
                                </div>
                            @endforeach
                        </div>
                    </td>
                </tr>
            @endif
            <!-- Поля доступа - отображаются только если разрешено -->
            @if($hasFields && 
                isset($selectedPermissions[$permId]) && 
                $selectedPermissions[$permId] &&
                isset($expandedPermissionFields[$permId]))
                <tr class="bg-blue-50">
                    <td colspan="{{ $showSource ? 5 : 4 }}" class="px-3 py-2">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-2">
                            @foreach($permFields as $field)
                                @php
                                    $fieldId = is_array($field) ? $field['id'] : $field->id;
                                    $fieldName = is_array($field) ? $field['name'] : $field->name;
                                    $fieldDefaultValue = is_array($field) ? ($field['default_value'] ?? '') : $field->default_value;
                                    $isGlobal = is_array($field) ? ($field['is_global'] ?? false) : $field->is_global;
                                @endphp
                                <div class="flex flex-col">
                                    <label for="{{ $inputPrefix }}_field_{{ $permId }}_{{ $fieldId }}" class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                        {{ $fieldName }}:
                                        @if($isGlobal)
                                            <span class="text-blue-600 dark:text-blue-400 text-xs font-normal">({{ __('permission-registry::Global Field') }})</span>
                                        @endif
                                    </label>
                                    <input type="text" id="{{ $inputPrefix }}_field_{{ $permId }}_{{ $fieldId }}"
                                           wire:model="{{ $permissionType === 'dependent' ? 'dependentPermissionFields' : 'permissionFields' }}.{{ $permId }}.{{ $fieldId }}"
                                           class="mt-1 text-sm rounded-md border-gray-300 dark:border-neutral-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 @if($isGlobal) bg-gray-100 dark:bg-neutral-600 @else dark:bg-neutral-700 dark:text-gray-100 @endif"
                                           placeholder="{{ $fieldDefaultValue }}"
                                           @if($isGlobal) readonly @endif>
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
