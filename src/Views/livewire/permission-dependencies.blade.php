<div>
    <!-- Flash Messages -->
    @if($flashMessage)
        <div class="mb-4 bg-green-100 dark:bg-green-900 border-l-4 border-green-500 text-green-700 dark:text-green-200 p-4 rounded" role="alert">
            <p>{{ $flashMessage }}</p>
        </div>
    @endif

    @if($flashError)
        <div class="mb-4 bg-red-100 dark:bg-red-900 border-l-4 border-red-500 text-red-700 dark:text-red-200 p-4 rounded" role="alert">
            <p>{{ $flashError }}</p>
        </div>
    @endif

    <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                {{ __('permission-registry::Dependencies for') }}: {{ $permission->name }}
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('permission-registry::Configure which permissions must be granted or revoked before this permission.') }}
            </p>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200 dark:border-neutral-700 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button wire:click="setActiveTab('grant')"
                        class="@if($activeTab === 'grant') border-orange-500 text-orange-600 dark:text-orange-400 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    📥 {{ __('permission-registry::Grant Dependencies') }}
                </button>
                <button wire:click="setActiveTab('revoke')"
                        class="@if($activeTab === 'revoke') border-orange-500 text-orange-600 dark:text-orange-400 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    📤 {{ __('permission-registry::Revoke Dependencies') }}
                </button>
            </nav>
        </div>

        <!-- Description based on active tab -->
        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-md">
            @if($activeTab === 'grant')
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    <strong>{{ __('permission-registry::Grant Dependencies') }}:</strong>
                    {{ __('permission-registry::These permissions must be granted before this permission can be granted.') }}
                    <br><strong>{{ __('permission-registry::Strict') }}:</strong> {{ __('permission-registry::requires the permission to be fully granted.') }}
                    <br><strong>{{ __('permission-registry::Non-strict') }}:</strong> {{ __('permission-registry::requires only the global fields from that permission.') }}
                </p>
            @else
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    <strong>{{ __('permission-registry::Revoke Dependencies') }}:</strong>
                    {{ __('permission-registry::These permissions must be revoked before this permission can be revoked.') }}
                    <br>{{ __('permission-registry::When revoking, dependent permissions will be revoked first, then this permission.') }}
                </p>
            @endif
        </div>

        <!-- Dependencies List -->
        <div class="space-y-3 mb-6">
            @php
                $dependencies = $activeTab === 'grant' ? $this->grantDependencies : $this->revokeDependencies;
            @endphp

            @forelse($dependencies as $dependency)
                <div class="bg-gray-50 dark:bg-neutral-700 p-4 rounded-md flex items-center justify-between border border-gray-200 dark:border-neutral-600">
                    <div class="flex-1">
                        <div class="font-medium text-gray-900 dark:text-gray-100">
                            {{ $dependency->requiredPermission->service }}: {{ $dependency->requiredPermission->name }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $dependency->requiredPermission->description }}
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        @if($activeTab === 'grant')
                            <label class="inline-flex items-center">
                                <input type="checkbox" 
                                       wire:click="toggleStrict({{ $dependency->id }})"
                                       class="rounded text-orange-600 dark:bg-neutral-600 dark:border-neutral-500"
                                       {{ $dependency->is_strict ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('permission-registry::Strict') }}</span>
                            </label>
                        @endif
                        <button wire:click="removeDependency({{ $dependency->id }})"
                                wire:confirm="{{ __('permission-registry::Are you sure you want to remove this dependency?') }}"
                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                            🗑️
                        </button>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-neutral-700 rounded-md border border-gray-200 dark:border-neutral-600">
                    {{ __('permission-registry::No dependencies yet. Add one below.') }}
                </div>
            @endforelse
        </div>

        <!-- Add Dependency Form -->
        <div class="border-t border-gray-200 dark:border-neutral-700 pt-6">
            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
                {{ __('permission-registry::Add Dependency') }}
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('permission-registry::Permission') }}
                        <x-perm::field-hint
                            :title="__('permission-registry::hints.permission_dependencies_permission_title')"
                            :description="__('permission-registry::hints.permission_dependencies_permission_desc')"
                        />
                    </label>
                    <select wire:model="selectedPermissionId" 
                            class="w-full rounded-md border-gray-300 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-100">
                        <option value="">{{ __('permission-registry::Select permission...') }}</option>
                        @foreach($this->availablePermissions as $perm)
                            <option value="{{ $perm->id }}">{{ $perm->service }}: {{ $perm->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex space-x-2">
                    @if($activeTab === 'grant')
                        <label class="inline-flex items-center">
                            <input type="checkbox" 
                                   wire:model="isStrict"
                                   class="rounded text-orange-600 dark:bg-neutral-600 dark:border-neutral-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                {{ __('permission-registry::Strict') }}
                                <x-perm::field-hint
                                    :title="__('permission-registry::hints.permission_dependencies_is_strict_title')"
                                    :description="__('permission-registry::hints.permission_dependencies_is_strict_desc')"
                                />
                            </span>
                        </label>
                    @endif
                    <button wire:click="addDependency"
                            class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 whitespace-nowrap">
                        {{ __('permission-registry::Add') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
