<!-- Используется внутри Livewire компонента -->
<div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-700 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
        </svg>
        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('permission-registry::Permissions Management') }}</h4>
    </div>

    <!-- 1. Зависимые права -->
    <div class="mb-6">
        <h5 class="text-sm font-semibold mb-3 text-gray-700 dark:text-gray-300">{{ __('permission-registry::Dependent Permissions (from Positions and Groups)') }}</h5>
        <div class="bg-gray-50 dark:bg-neutral-900 p-4 rounded-lg border border-gray-200 dark:border-neutral-700">
            @if($this->dependentPermissions && $this->dependentPermissions->count() > 0)
                <x-pr::permissions-table 
                    :permissions="$this->dependentPermissions"
                    :selectedPermissions="$this->dependentSelectedPermissions"
                    :permissionFields="$this->dependentPermissionFields"
                    :expandedPermissionFields="$this->expandedDependentPermissionFields"
                    permissionType="dependent"
                    :showSource="true"
                    :dependentPermissionErrors="$this->dependentPermissionErrors"
                    :showContinueStepLink="count($this->completedPermissionsWithErrors ?? []) > 0"
                />

                <div class="mt-4 flex justify-end">
                    <button wire:click="saveUserPermissions"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 border border-transparent rounded-lg disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 transform hover:scale-105">
                        <span wire:loading.remove wire:target="saveUserPermissions">{{ __('permission-registry::Save Permissions') }}</span>
                        <span wire:loading wire:target="saveUserPermissions" class="inline-flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('permission-registry::Processing...') }}
                        </span>
                    </button>
                </div>

                <x-pr::debug-panel :isProcessing="$this->isProcessing" :completedPermissionsWithErrors="$this->completedPermissionsWithErrors" :processingPermissions="$this->processingPermissions" />
                <x-pr::completed-triggers-panel :completedPermissionsWithErrors="$this->completedPermissionsWithErrors" :isProcessing="$this->isProcessing" />
                <x-pr::processing-triggers-panel :processingPermissions="$this->processingPermissions" :isProcessing="$this->isProcessing" />
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('permission-registry::No dependent permissions found') }}</p>
            @endif
        </div>
    </div>

    <!-- 2. Прямые права -->
    <div>
        <h5 class="text-sm font-semibold mb-3 text-gray-700 dark:text-gray-300">{{ __('permission-registry::Direct Permissions') }}</h5>
        <div class="bg-gray-50 dark:bg-neutral-900 p-4 rounded-lg border border-gray-200 dark:border-neutral-700">
            @if($this->availablePermissions && $this->availablePermissions->count() > 0)
                <div class="mb-4">
                    <input type="text" wire:model.live="permissionSearch" placeholder="{{ __('permission-registry::Search permissions') }}"
                           class="text-sm rounded-lg border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 w-full">
                </div>

                <x-pr::permissions-table 
                    :permissions="$this->availablePermissions"
                    :selectedPermissions="$this->selectedPermissions"
                    :permissionFields="$this->permissionFields"
                    :expandedPermissionFields="$this->expandedPermissionFields"
                    permissionType="direct"
                    :showSource="false"
                    :permissionStatuses="$this->permissionStatuses"
                />

                <div class="mt-4 flex justify-end">
                    <button wire:click="saveUserPermissions"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 border border-transparent rounded-lg disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 transform hover:scale-105">
                        <span wire:loading.remove wire:target="saveUserPermissions">{{ __('permission-registry::Save Permissions') }}</span>
                        <span wire:loading wire:target="saveUserPermissions" class="inline-flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('permission-registry::Processing...') }}
                        </span>
                    </button>
                </div>

                <x-pr::debug-panel :isProcessing="$this->isProcessing" :completedPermissionsWithErrors="$this->completedPermissionsWithErrors" :processingPermissions="$this->processingPermissions" />
                <x-pr::completed-triggers-panel :completedPermissionsWithErrors="$this->completedPermissionsWithErrors" :isProcessing="$this->isProcessing" />
                <x-pr::processing-triggers-panel :processingPermissions="$this->processingPermissions" :isProcessing="$this->isProcessing" />
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('permission-registry::No permissions available') }}</p>
            @endif
        </div>
    </div>
</div>
