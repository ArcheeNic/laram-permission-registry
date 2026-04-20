<!-- Используется внутри Livewire компонента, имеет доступ ко всем его свойствам -->
@if($this->selectedUser)
    <!-- Заголовок модального окна -->
    <div class="sticky top-0 z-10 bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-700 dark:to-indigo-700 px-6 py-5 border-b border-blue-700">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <!-- Аватар -->
                @php
                    $nameParts = explode(' ', $this->selectedUser->name);
                    $initials = '';
                    foreach (array_slice($nameParts, 0, 2) as $part) {
                        $initials .= mb_substr($part, 0, 1);
                    }
                    $initials = mb_strtoupper($initials);
                @endphp
                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm
                            flex items-center justify-center text-white font-bold text-lg
                            shadow-lg border-2 border-white/30">
                    {{ $initials }}
                </div>
                
                <div>
                    <h2 class="text-2xl font-bold text-white"
                        style="font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;">
                        {{ $this->selectedUser->name }}
                    </h2>
                    <div class="flex items-center gap-2 mt-1">
                        <p class="text-blue-100 text-sm font-mono">
                            ID: {{ $this->selectedUser->id }}
                        </p>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $this->selectedUser->isActive() ? 'bg-emerald-100/90 text-emerald-800' : 'bg-amber-100/90 text-amber-800' }}">
                            {{ $this->selectedUserStatusLabel }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if($this->selectedUser->isActive())
                    <button
                        wire:click="confirmFire"
                        wire:target="confirmFire,fireUser"
                        wire:loading.attr="disabled"
                        data-confirm-message="{{ __('permission-registry::messages.confirm_fire') }}"
                        onclick="return confirm(this.dataset.confirmMessage)"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                        <svg wire:loading wire:target="confirmFire,fireUser" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity="0.25"></circle>
                            <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" fill="none" stroke-linecap="round"></path>
                        </svg>
                        <span wire:loading.remove wire:target="confirmFire,fireUser">{{ __('permission-registry::messages.fire') }}</span>
                        <span wire:loading wire:target="confirmFire,fireUser">{{ __('permission-registry::messages.firing_in_progress') }}</span>
                    </button>
                @else
                    <div class="flex items-center gap-2">
                        <label for="hire-category" class="text-sm text-white/90">
                            {{ __('permission-registry::messages.employee_category') }}
                            <x-perm::field-hint
                                :title="__('permission-registry::hints.user_edit_hire_category_title')"
                                :description="__('permission-registry::hints.user_edit_hire_category_desc')"
                            />
                        </label>
                        <select
                            id="hire-category"
                            wire:model.live="selectedHireCategory"
                            wire:loading.attr="disabled"
                            wire:target="hireUser"
                            class="rounded-lg border border-white/30 bg-white/15 text-sm text-white focus:border-white/60 focus:ring-0 disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            @foreach($this->employeeCategoryOptions as $categoryValue => $categoryLabel)
                                <option value="{{ $categoryValue }}" class="text-gray-900">{{ $categoryLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button
                        wire:click="hireUser"
                        wire:target="hireUser"
                        wire:loading.attr="disabled"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                        <svg wire:loading wire:target="hireUser" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity="0.25"></circle>
                            <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" fill="none" stroke-linecap="round"></path>
                        </svg>
                        <span wire:loading.remove wire:target="hireUser">{{ __('permission-registry::messages.hire') }}</span>
                        <span wire:loading wire:target="hireUser">{{ __('permission-registry::messages.hiring_in_progress') }}</span>
                    </button>
                @endif

                @if($this->selectedUserHasPendingHrConflict)
                    <button
                        wire:click="openHireConflictModal"
                        type="button"
                        class="inline-flex items-center px-3 py-1.5 rounded-lg bg-amber-600 text-white text-sm font-medium hover:bg-amber-700 transition-colors"
                    >
                        {{ __('permission-registry::messages.requires_action') }}
                        <span class="ml-1.5 rounded-full bg-white/20 px-1.5 py-0.5 text-xs">
                            {{ $this->selectedUserPendingHrConflictsCount }}
                        </span>
                    </button>
                @endif

                <!-- Кнопка закрытия -->
                <button wire:click="closeEditModal"
                        type="button"
                        class="text-white/80 hover:text-white hover:bg-white/10 rounded-full p-2
                               transition-all duration-200 hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Контент модального окна -->
    <div class="overflow-y-auto max-h-[calc(90vh-100px)] bg-gray-50 dark:bg-neutral-800">
        <div class="p-6 space-y-6">
            <!-- Flash сообщения -->
            <x-pr::flash-message type="success" :message="$this->flashMessage" />
            <x-pr::flash-message type="error" :message="$this->flashError" />
            <x-pr::flash-message type="warning" :message="$this->flashWarning" />

            <!-- Общая панель прогресса выдачи прав -->
            @if($this->hasPendingPermissions && $this->totalPermissionsToProcess > 0)
                <x-pr::progress-panel 
                    :processed="$this->processedPermissions" 
                    :total="$this->totalPermissionsToProcess" />
            @endif

            <!-- Привязка к пользователю приложения -->
            @include('permission-registry::components.app-user-link-section')

            <!-- Глобальные поля -->
            @if($this->globalFieldDefinitions && $this->globalFieldDefinitions->count() > 0)
                @include('permission-registry::components.global-fields-section')
            @endif

            <!-- Назначение должности -->
            <x-pr::positions-section :user="$this->selectedUser" />

            <!-- Управление группами -->
            <x-pr::groups-section :user="$this->selectedUser" />

            <!-- Permissions Management -->
            @include('permission-registry::components.permissions-section')
        </div>
    </div>
@endif
