<div wire:poll.2s="checkPermissionStatus" class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50 dark:from-neutral-900 dark:via-neutral-800 dark:to-neutral-900 p-6">
    <div class="w-full">
        <!-- Заголовок и поиск -->
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100"
                        style="font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;">
                        {{ __('permission-registry::Users Management') }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        {{ __('permission-registry::Manage user permissions and roles') }}
                    </p>
                </div>
                
                <!-- Кнопка создания пользователя -->
                <button wire:click="toggleCreateForm" 
                        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    {{ __('permission-registry::Create User') }}
                </button>
            </div>

            <div class="mt-4 space-y-2">
                <x-pr::flash-message type="success" :message="$this->flashMessage" />
                <x-pr::flash-message type="error" :message="$this->flashError" />
                <x-pr::flash-message type="warning" :message="$this->flashWarning" />
                @if(count($bulkResultFailures) > 0 && !$showBulkResultModal)
                    <button type="button"
                            wire:click="$set('showBulkResultModal', true)"
                            class="inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 dark:text-blue-300 dark:bg-blue-900/20 dark:hover:bg-blue-900/30">
                        {{ __('permission-registry::messages.bulk_operation_details') }}
                    </button>
                @endif
            </div>
            
            <!-- Поиск -->
            <div class="mt-6">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" 
                           type="text" 
                           placeholder="{{ __('permission-registry::Search users by name or ID') }}"
                           class="w-full pl-12 pr-4 py-4 text-lg rounded-xl border-2 border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                </div>
            </div>

            <!-- Фильтры и сортировка -->
            <div class="mt-4 flex flex-col sm:flex-row sm:items-end gap-3 flex-wrap">
                <div class="flex-1 min-w-[140px] max-w-[200px]">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('permission-registry::Status') }}</label>
                    <select wire:model.live="filterStatus"
                            class="w-full rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-800 dark:text-gray-100 text-sm py-2 px-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">{{ __('permission-registry::All statuses') }}</option>
                        <option value="active">{{ __('permission-registry::messages.active') }}</option>
                        <option value="deactivated">{{ __('permission-registry::messages.deactivated') }}</option>
                    </select>
                </div>

                <div class="flex-1 min-w-[140px] max-w-[200px]">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('permission-registry::messages.employee_category') }}</label>
                    <select wire:model.live="filterCategory"
                            class="w-full rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-800 dark:text-gray-100 text-sm py-2 px-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">{{ __('permission-registry::All categories') }}</option>
                        <option value="staff">{{ __('permission-registry::messages.employee_category_staff') }}</option>
                        <option value="contractor">{{ __('permission-registry::messages.employee_category_contractor') }}</option>
                    </select>
                </div>

                <div class="flex-1 min-w-[140px] max-w-[200px]">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('permission-registry::Group') }}</label>
                    <select wire:model.live="filterGroup"
                            class="w-full rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-800 dark:text-gray-100 text-sm py-2 px-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">{{ __('permission-registry::All groups') }}</option>
                        @foreach($availableGroups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1 min-w-[140px] max-w-[200px]">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('permission-registry::Sort by') }}</label>
                    <select wire:model.live="sortField"
                            class="w-full rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-800 dark:text-gray-100 text-sm py-2 px-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="created_at">{{ __('permission-registry::Created At') }}</option>
                        <option value="name">{{ __('permission-registry::Name') }}</option>
                        <option value="status">{{ __('permission-registry::Status') }}</option>
                        <option value="employee_category">{{ __('permission-registry::messages.employee_category') }}</option>
                        <option value="updated_at">{{ __('permission-registry::Updated At') }}</option>
                        <option value="id">{{ __('permission-registry::ID') }}</option>
                    </select>
                </div>

                <div class="flex-shrink-0">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">&nbsp;</label>
                    <button wire:click="$set('sortDirection', '{{ $sortDirection === 'asc' ? 'desc' : 'asc' }}')"
                            class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-neutral-700 text-sm transition-colors"
                            title="{{ $sortDirection === 'asc' ? __('permission-registry::Ascending') : __('permission-registry::Descending') }}">
                        @if($sortDirection === 'asc')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        @endif
                    </button>
                </div>

                <div class="flex-1 min-w-[100px] max-w-[130px]">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('permission-registry::Per page') }}</label>
                    <select wire:model.live="perPage"
                            class="w-full rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-800 dark:text-gray-100 text-sm py-2 px-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="12">12</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>

                @if($hasActiveFilters)
                    <div class="flex-shrink-0">
                        <label class="block text-xs font-medium text-transparent mb-1">&nbsp;</label>
                        <button wire:click="resetFilters"
                                class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-red-600 dark:text-red-400 border border-red-300 dark:border-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            {{ __('permission-registry::Reset filters') }}
                        </button>
                    </div>
                @endif
            </div>

            <!-- Active filter chips -->
            @if($hasActiveFilters)
                <div class="mt-3 flex flex-wrap gap-2">
                    @if($filterStatus !== '')
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                            {{ __('permission-registry::Status') }}: {{ $filterStatus === 'active' ? __('permission-registry::messages.active') : __('permission-registry::messages.deactivated') }}
                            <button type="button" wire:click="$set('filterStatus', '')" class="ml-1 hover:text-blue-600 dark:hover:text-blue-100" aria-label="{{ __('permission-registry::Reset filters') }}: {{ __('permission-registry::Status') }}">&times;</button>
                        </span>
                    @endif
                    @if($filterCategory !== '')
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300">
                            {{ __('permission-registry::messages.employee_category') }}: {{ $filterCategory === 'staff' ? __('permission-registry::messages.employee_category_staff') : __('permission-registry::messages.employee_category_contractor') }}
                            <button type="button" wire:click="$set('filterCategory', '')" class="ml-1 hover:text-purple-600 dark:hover:text-purple-100" aria-label="{{ __('permission-registry::Reset filters') }}: {{ __('permission-registry::messages.employee_category') }}">&times;</button>
                        </span>
                    @endif
                    @if($filterGroup !== '')
                        @php $activeGroup = $availableGroups->firstWhere('id', $filterGroup); @endphp
                        @if($activeGroup)
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300">
                                {{ __('permission-registry::Group') }}: {{ $activeGroup->name }}
                                <button type="button" wire:click="$set('filterGroup', '')" class="ml-1 hover:text-emerald-600 dark:hover:text-emerald-100" aria-label="{{ __('permission-registry::Reset filters') }}: {{ __('permission-registry::Group') }}">&times;</button>
                            </span>
                        @endif
                    @endif
                    @if($search !== '')
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-neutral-700 text-gray-800 dark:text-gray-300">
                            {{ __('permission-registry::Search') }}: {{ Str::limit($search, 30) }}
                            <button type="button" wire:click="$set('search', '')" class="ml-1 hover:text-gray-600 dark:hover:text-gray-100" aria-label="{{ __('permission-registry::Reset filters') }}: {{ __('permission-registry::Search') }}">&times;</button>
                        </span>
                    @endif
                </div>
            @endif
        </div>

        <!-- Форма создания пользователя -->
        @if($showCreateForm)
            <div class="mb-6 bg-white dark:bg-neutral-800 rounded-xl shadow-lg border border-gray-200 dark:border-neutral-700 p-6"
                 x-data
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform -translate-y-4"
                 x-transition:enter-end="opacity-100 transform translate-y-0">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        {{ __('permission-registry::Create New User') }}
                    </h3>
                    <button wire:click="toggleCreateForm" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                @if($requiredFields && count($requiredFields) > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        @foreach($requiredFields as $field)
                            <div>
                                <label for="field_{{ $field['id'] }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $field['name'] }} *
                                </label>
                                <input type="text" 
                                       id="field_{{ $field['id'] }}"
                                       wire:model.live.debounce.500ms="newUserFields.{{ $field['id'] }}"
                                       placeholder="{{ $field['default_value'] ?? '' }}"
                                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('newUserFields.' . $field['id']) 
                                    <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                                @enderror
                                @if(isset($duplicateHints[$field['id']]) && $duplicateHints[$field['id']] > 0)
                                    <span class="text-amber-600 dark:text-amber-400 text-sm mt-1 flex items-center gap-1">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                        </svg>
                                        {{ __('permission-registry::messages.duplicate_users_found', ['count' => $duplicateHints[$field['id']]]) }}
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        {{ __('permission-registry::No required fields configured. Please add global fields with "Required on user create" option.') }}
                    </p>
                @endif
                
                <div class="flex justify-end space-x-3">
                    <button wire:click="toggleCreateForm" 
                            class="px-6 py-2 border border-gray-300 dark:border-neutral-600 rounded-lg hover:bg-gray-50 dark:hover:bg-neutral-700 dark:text-gray-100 transition-colors">
                        {{ __('permission-registry::Cancel') }}
                    </button>
                    <button wire:click="createUser" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all transform hover:scale-105"
                            @if(!$requiredFields || count($requiredFields) === 0) disabled @endif>
                        {{ __('permission-registry::Create') }}
                    </button>
                </div>
            </div>
        @endif

        <!-- Список пользователей -->
        <x-pr::users-table
            :users="$users"
            :sortField="$sortField"
            :sortDirection="$sortDirection"
            :bulkSelectedIds="$bulkSelectedIds"
            :currentPageAllSelected="$this->currentPageAllSelected"
        />

        <!-- Пагинация -->
        @if($users->hasPages())
            <div class="mt-8">
                {{ $users->links() }}
            </div>
        @endif

        <!-- Пустое состояние -->
        @if($users->isEmpty())
            <div class="text-center py-16">
                <svg class="w-24 h-24 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    {{ __('permission-registry::No users found') }}
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    @if($search)
                        {{ __('permission-registry::Try adjusting your search criteria') }}
                    @else
                        {{ __('permission-registry::Create your first user to get started') }}
                    @endif
                </p>
            </div>
        @endif
    </div>

    @if($this->bulkSelectedCount > 0)
        <div class="fixed bottom-0 left-0 right-0 z-40 border-t border-gray-200 bg-white/95 backdrop-blur dark:border-neutral-700 dark:bg-neutral-800/95">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-3 px-4 py-3 md:flex-row md:items-end md:justify-between">
                <div class="text-sm font-medium text-gray-700 dark:text-gray-200">
                    {{ __('permission-registry::messages.bulk_selected_count', ['count' => $this->bulkSelectedCount]) }}
                </div>
                <div class="grid grid-cols-1 gap-2 md:grid-cols-4 md:items-end">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                            {{ __('permission-registry::messages.employee_category') }}
                        </label>
                        <select wire:model="selectedHireCategory"
                                class="mb-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-neutral-600 dark:bg-neutral-800 dark:text-gray-100">
                            @foreach($this->employeeCategoryOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                            {{ __('permission-registry::messages.hire') }}
                        </label>
                        <button type="button"
                                wire:click="bulkHireUsers"
                                @disabled($this->bulkHireEligibleCount === 0)
                                class="w-full rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50">
                            {{ __('permission-registry::messages.hire') }}
                            <span class="ml-1 text-xs">({{ $this->bulkHireEligibleCount }}/{{ $this->bulkSelectedCount }})</span>
                        </button>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                            {{ __('permission-registry::Group') }}
                        </label>
                        <select wire:model="bulkGroupId"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-neutral-600 dark:bg-neutral-800 dark:text-gray-100">
                            <option value="">{{ __('permission-registry::Select a group') }}</option>
                            @foreach($availableGroups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <button type="button"
                                wire:click="bulkAssignGroup"
                                @disabled($bulkGroupId === '')
                                class="w-full rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700 disabled:cursor-not-allowed disabled:opacity-50">
                            {{ __('permission-registry::messages.assign_group') }}
                        </button>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                            {{ __('permission-registry::Position') }}
                        </label>
                        <select wire:model="bulkPositionId"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-neutral-600 dark:bg-neutral-800 dark:text-gray-100">
                            <option value="">{{ __('permission-registry::Select a position') }}</option>
                            @foreach($availablePositions as $position)
                                <option value="{{ $position->id }}">{{ $position->name }}</option>
                            @endforeach
                        </select>
                        <button type="button"
                                wire:click="bulkAssignPosition"
                                @disabled($bulkPositionId === '')
                                class="mt-2 w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                            {{ __('permission-registry::messages.assign_position') }}
                        </button>
                    </div>
                </div>
                <button type="button"
                        wire:click="clearBulkSelection"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-neutral-600 dark:text-gray-200 dark:hover:bg-neutral-700">
                    {{ __('permission-registry::messages.clear_selection') }}
                </button>
            </div>
        </div>
    @endif

    <!-- Модальное окно редактирования -->
    <div x-data="{ show: @entangle('showEditModal').live }"
         x-show="show"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="$wire.closeEditModal()"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        
        <!-- Фон с blur -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"
             @click="$wire.closeEditModal()"></div>
        
        <!-- Модальное окно -->
        <div class="flex min-h-screen items-center justify-center p-4">
            <div x-show="show"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-10"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-10"
                 class="relative w-full max-w-7xl bg-white dark:bg-neutral-900 rounded-2xl shadow-2xl overflow-hidden"
                 @click.stop>
                
                @include('permission-registry::components.user-edit-modal')
        </div>
    </div>
</div>

<!-- Модальное окно для ручной выдачи права -->
@if($showManualGrantModal)
<div x-data="{ show: @entangle('showManualGrantModal') }"
     x-show="show"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @keydown.escape.window="$wire.closeManualGrantModal()"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="$wire.closeManualGrantModal()"></div>
    
    <div class="flex min-h-screen items-center justify-center p-4">
        <div x-show="show"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-lg bg-white dark:bg-neutral-800 rounded-xl shadow-2xl overflow-hidden"
             @click.stop>
            
            <div class="px-6 py-4 border-b border-gray-200 dark:border-neutral-700">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ __('permission-registry::Manual Grant') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('permission-registry::Fill required global fields') }}
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="px-6 py-4">
                @foreach($manualGrantMissingFields as $field)
                <div class="mb-4 last:mb-0">
                    <label for="manual_grant_field_{{ $field['id'] }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $field['name'] }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="manual_grant_field_{{ $field['id'] }}"
                           wire:model="manualGrantFieldValues.{{ $field['id'] }}"
                           class="block w-full rounded-lg border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-gray-100 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                           placeholder="{{ $field['name'] }}">
                </div>
                @endforeach
            </div>
            
            <div class="px-6 py-4 bg-gray-50 dark:bg-neutral-700 flex justify-end gap-3">
                <button type="button"
                        wire:click="closeManualGrantModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-neutral-600 border border-gray-300 dark:border-neutral-500 rounded-lg hover:bg-gray-50 dark:hover:bg-neutral-500 transition-colors">
                    {{ __('permission-registry::Cancel') }}
                </button>
                <button type="button"
                        wire:click="saveGlobalFieldsAndRetryGrant"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                    {{ __('permission-registry::Save and Grant') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@if($showHireConflictModal)
<div x-data="{ show: @entangle('showHireConflictModal') }"
     x-show="show"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @keydown.escape.window="$wire.closeHireConflictModal()"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">

    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="$wire.closeHireConflictModal()"></div>

    <div class="flex min-h-screen items-center justify-center p-4">
        <div x-show="show"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-xl bg-white dark:bg-neutral-800 rounded-xl shadow-2xl overflow-hidden"
             @click.stop>

            <div class="px-6 py-4 border-b border-gray-200 dark:border-neutral-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('permission-registry::messages.hr_email_conflict_title') }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('permission-registry::messages.hr_email_conflict_description') }}
                </p>
            </div>

            <div class="px-6 py-4 space-y-4">
                @if(!empty($selectedHrConflictMeta['email']))
                    <div class="text-sm text-gray-700 dark:text-gray-200">
                        <span class="font-medium">{{ __('permission-registry::Email') }}:</span>
                        <span>{{ $selectedHrConflictMeta['email'] }}</span>
                    </div>
                @endif
                @if(!empty($selectedHrConflictMeta['suggested_email']))
                    <div class="text-sm text-gray-700 dark:text-gray-200">
                        <span class="font-medium">{{ __('permission-registry::messages.hr_suggested_email') }}:</span>
                        <span>{{ $selectedHrConflictMeta['suggested_email'] }}</span>
                    </div>
                @endif

                <div class="space-y-3">
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <input type="radio" wire:model.live="hireConflictStrategy" value="increment">
                        {{ __('permission-registry::messages.hr_conflict_increment') }}
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <input type="radio" wire:model.live="hireConflictStrategy" value="custom_email">
                        {{ __('permission-registry::messages.hr_conflict_custom_email') }}
                    </label>
                    @if($hireConflictStrategy === 'custom_email')
                        <input type="email"
                               wire:model.live="hireConflictCustomEmail"
                               placeholder="user@example.com"
                               class="w-full rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @endif
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <input type="radio" wire:model.live="hireConflictStrategy" value="cancel">
                        {{ __('permission-registry::messages.hr_conflict_cancel') }}
                    </label>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 dark:bg-neutral-700 flex justify-end gap-3">
                <button type="button"
                        wire:click="closeHireConflictModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-neutral-600 border border-gray-300 dark:border-neutral-500 rounded-lg hover:bg-gray-50 dark:hover:bg-neutral-500 transition-colors">
                    {{ __('permission-registry::Cancel') }}
                </button>
                <button type="button"
                        wire:click="resolveHireConflict"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                    {{ __('permission-registry::messages.resolve') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@if($showBulkResultModal)
<div x-data="{ show: @entangle('showBulkResultModal') }"
     x-show="show"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="$wire.closeBulkResultModal()"></div>
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="relative w-full max-w-2xl rounded-xl bg-white shadow-2xl dark:bg-neutral-800" @click.stop>
            <div class="border-b border-gray-200 px-6 py-4 dark:border-neutral-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('permission-registry::messages.bulk_operation_details') }}
                </h3>
            </div>
            <div class="space-y-4 px-6 py-4">
                <div>
                    <h4 class="mb-2 text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ __('permission-registry::messages.bulk_succeeded') }}</h4>
                    <p class="text-sm text-gray-700 dark:text-gray-200">{{ implode(', ', $bulkResultSuccessIds) ?: '—' }}</p>
                </div>
                <div>
                    <h4 class="mb-2 text-sm font-semibold text-amber-700 dark:text-amber-300">{{ __('permission-registry::messages.bulk_skipped') }}</h4>
                    <p class="text-sm text-gray-700 dark:text-gray-200">{{ implode(', ', $bulkResultSkippedIds) ?: '—' }}</p>
                </div>
                <div>
                    <h4 class="mb-2 text-sm font-semibold text-red-700 dark:text-red-300">{{ __('permission-registry::messages.bulk_failed') }}</h4>
                    @if(count($bulkResultFailures) > 0)
                        <ul class="space-y-1 text-sm text-gray-700 dark:text-gray-200">
                            @foreach($bulkResultFailures as $failure)
                                <li>#{{ $failure['virtual_user_id'] }} — {{ $failure['message'] }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-700 dark:text-gray-200">—</p>
                    @endif
                </div>
            </div>
            <div class="flex justify-end border-t border-gray-200 px-6 py-4 dark:border-neutral-700">
                <button type="button"
                        wire:click="closeBulkResultModal"
                        class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 dark:bg-neutral-700 dark:hover:bg-neutral-600">
                    {{ __('permission-registry::messages.close') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif
</div>
