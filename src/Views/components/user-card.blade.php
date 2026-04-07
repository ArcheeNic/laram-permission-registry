@props([
    'user',
    'isSelected' => false,
])

@php
    // Генерация градиента на основе ID пользователя
    $gradients = [
        'from-purple-500 via-pink-500 to-red-500',
        'from-blue-500 via-cyan-500 to-teal-500',
        'from-indigo-500 via-purple-500 to-pink-500',
        'from-green-500 via-emerald-500 to-cyan-500',
        'from-orange-500 via-red-500 to-pink-500',
        'from-fuchsia-500 via-purple-500 to-indigo-500',
        'from-rose-500 via-pink-500 to-purple-500',
        'from-amber-500 via-orange-500 to-red-500',
    ];
    
    $gradientIndex = $user->id % count($gradients);
    $gradient = $gradients[$gradientIndex];
    
    // Генерация инициалов
    $nameParts = explode(' ', $user->name);
    $initials = '';
    foreach (array_slice($nameParts, 0, 2) as $part) {
        $initials .= mb_substr($part, 0, 1);
    }
    $initials = mb_strtoupper($initials);
    
    // Загружаем недостающие связи для карточки
    $user->loadMissing([
        'positions.parent.parent.parent.parent',
        'groups',
        'fieldValues.field',
        'grantedPermissions' => function ($query) {
            $query->where('enabled', true)
                ->with('permission')
                ->orderByDesc('granted_at')
                ->orderByDesc('id');
        },
    ]);
    
    $positionsCount = $user->positions->count();
    $groupsCount = $user->groups->count();
    $permissionsCount = $user->grantedPermissions->count();
    $email = $user->email_for_display;
    $latestGrantedPermission = $user->grantedPermissions->first();
    $latestGrantedAt = $latestGrantedPermission?->granted_at?->format('d.m.Y H:i');
@endphp

<div wire:click="openEditModal({{ $user->id }})"
     class="group relative bg-white dark:bg-neutral-800 rounded-xl shadow-md hover:shadow-2xl
            border-2 {{ $isSelected ? 'border-blue-500 ring-4 ring-blue-200 dark:ring-blue-900' : 'border-gray-200 dark:border-neutral-700' }}
            transition-all duration-300 ease-out cursor-pointer
            hover:scale-105 hover:-translate-y-1 overflow-hidden">
    <div class="absolute left-3 top-3 z-10" wire:click.stop>
        <input type="checkbox"
               @checked($isSelected)
               wire:click.stop="toggleBulkSelect({{ $user->id }})"
               aria-label="{{ __('permission-registry::messages.select_user') }} #{{ $user->id }}"
               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
    </div>
    
    <!-- Градиентная полоса сверху -->
    <div class="h-2 bg-gradient-to-r {{ $gradient }}"></div>
    
    <!-- Контент карточки -->
    <div class="p-5">
        <!-- Аватар и основная информация -->
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center space-x-3">
                <!-- Аватар с инициалами -->
                <div class="relative flex-shrink-0">
                    <div class="w-14 h-14 rounded-full bg-gradient-to-br {{ $gradient }} 
                                flex items-center justify-center text-white font-bold text-lg
                                shadow-lg group-hover:shadow-xl transition-shadow duration-300">
                        {{ $initials }}
                    </div>
                    <!-- Индикатор статуса -->
                    <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white dark:border-neutral-800
                                animate-pulse"></div>
                </div>
                
                <!-- Имя и ID -->
                <div class="flex-1 min-w-0">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 truncate
                               group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors"
                        style="font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;">
                        {{ $user->name }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                        ID: {{ $user->id }}
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-300 truncate">
                        {{ $email ?: '—' }}
                    </p>
                    @if(($user->pending_hr_conflicts_count ?? 0) > 0)
                        <span class="inline-flex mt-1 items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                            {{ __('permission-registry::messages.requires_action') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Статистика в виде бейджей -->
        <div class="flex items-center gap-2 mb-4 flex-wrap">
            <!-- Должности -->
            <div class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium
                        bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300
                        border border-blue-200 dark:border-blue-800
                        transition-all duration-200 hover:scale-110">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <span class="font-semibold">{{ $positionsCount }}</span>
            </div>
            
            <!-- Группы -->
            <div class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium
                        bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300
                        border border-purple-200 dark:border-purple-800
                        transition-all duration-200 hover:scale-110">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <span class="font-semibold">{{ $groupsCount }}</span>
            </div>
            
            <!-- Права -->
            <div class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium
                        bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300
                        border border-emerald-200 dark:border-emerald-800
                        transition-all duration-200 hover:scale-110">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
                <span class="font-semibold">{{ $permissionsCount }}</span>
            </div>
        </div>

        @if($user->grantedPermissions->isNotEmpty())
            <div class="mb-3">
                <h4 class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">
                    {{ __('permission-registry::Granted Permissions') }}
                </h4>
                <div class="flex flex-wrap gap-1.5 mb-2">
                    @foreach($user->grantedPermissions->take(2) as $grantedPermission)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium
                                     bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300
                                     border border-emerald-200 dark:border-emerald-800">
                            {{ $grantedPermission->permission->name }}
                        </span>
                    @endforeach
                    @if($user->grantedPermissions->count() > 2)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium
                                     bg-gray-100 dark:bg-neutral-700 text-gray-600 dark:text-gray-300">
                            +{{ $user->grantedPermissions->count() - 2 }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('permission-registry::Granted at') }}:
                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ $latestGrantedAt ?: '—' }}</span>
                </p>
            </div>
        @endif
        
        <!-- Список должностей -->
        @if($user->positions->isNotEmpty())
            <div class="mb-3">
                <h4 class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">
                    {{ __('permission-registry::Positions') }}
                </h4>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($user->positions->take(3) as $position)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium
                                     bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20
                                     text-blue-700 dark:text-blue-300
                                     border border-blue-200 dark:border-blue-800">
                            @include('permission-registry::components.position-hierarchy-label', ['position' => $position])
                        </span>
                    @endforeach
                    @if($user->positions->count() > 3)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium
                                     bg-gray-100 dark:bg-neutral-700 text-gray-600 dark:text-gray-300">
                            +{{ $user->positions->count() - 3 }}
                        </span>
                    @endif
                </div>
            </div>
        @endif
        
        <!-- Список групп -->
        @if($user->groups->isNotEmpty())
            <div>
                <h4 class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">
                    {{ __('permission-registry::Groups') }}
                </h4>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($user->groups->take(3) as $group)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium
                                     bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20
                                     text-purple-700 dark:text-purple-300
                                     border border-purple-200 dark:border-purple-800">
                            {{ $group->name }}
                        </span>
                    @endforeach
                    @if($user->groups->count() > 3)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium
                                     bg-gray-100 dark:bg-neutral-700 text-gray-600 dark:text-gray-300">
                            +{{ $user->groups->count() - 3 }}
                        </span>
                    @endif
                </div>
            </div>
        @endif
        
        <!-- Пустое состояние если нет должностей и групп -->
        @if($user->positions->isEmpty() && $user->groups->isEmpty())
            <div class="text-center py-4">
                <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('permission-registry::No positions or groups assigned') }}
                </p>
            </div>
        @endif
    </div>
    
    <!-- Hover индикатор "Нажмите для редактирования" -->
    <div class="absolute inset-0 bg-gradient-to-t from-blue-600/90 to-transparent
                opacity-0 group-hover:opacity-100 transition-opacity duration-300
                flex items-end justify-center pb-4 pointer-events-none rounded-xl">
        <span class="text-white font-semibold text-sm flex items-center space-x-2 transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
            </svg>
            <span>{{ __('permission-registry::Click to edit') }}</span>
        </span>
    </div>
</div>
