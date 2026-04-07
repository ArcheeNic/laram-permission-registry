@php
    $level = $level ?? 0;
    $hasChildren = $position->children && $position->children->count() > 0;
    $isOpen = in_array($position->id, $openPositions ?? []);
    $levelColors = [
        'bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-800/20',
        'bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20',
        'bg-gradient-to-br from-pink-50 to-pink-100 dark:from-pink-900/20 dark:to-pink-800/20',
        'bg-gradient-to-br from-rose-50 to-rose-100 dark:from-rose-900/20 dark:to-rose-800/20',
    ];
    $levelBorders = [
        'border-l-4 border-indigo-400 dark:border-indigo-500',
        'border-l-4 border-purple-400 dark:border-purple-500',
        'border-l-4 border-pink-400 dark:border-pink-500',
        'border-l-4 border-rose-400 dark:border-rose-500',
    ];
    $colorClass = $levelColors[$level % count($levelColors)];
    $borderClass = $levelBorders[$level % count($levelBorders)];
@endphp

<div class="position-card" style="margin-left: {{ $level * 1.5 }}rem;">
    <div class="relative {{ $colorClass }} {{ $borderClass }} rounded-lg shadow-md hover:shadow-xl transition-all duration-300 p-5 mb-4 dark:bg-neutral-800">
        <!-- Заголовок с кнопкой раскрытия -->
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 mb-2">
                    @if($hasChildren)
                        <button 
                            wire:click="togglePosition({{ $position->id }})"
                            class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-white dark:bg-neutral-700 shadow-sm hover:shadow-md transition-all duration-200 text-gray-700 dark:text-gray-200"
                            aria-label="{{ $isOpen ? __('permission-registry::Collapse') : __('permission-registry::Expand') }}"
                        >
                            <svg class="w-4 h-4 transition-transform duration-200 {{ $isOpen ? 'rotate-90' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    @else
                        <div class="w-8 h-8 flex-shrink-0"></div>
                    @endif
                    
                    <a href="{{ route('permission-registry::positions.show', $position) }}" 
                       class="text-lg font-semibold text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors truncate">
                        {{ $position->name }}
                    </a>
                </div>
                
                @if($position->description)
                    <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2 ml-11">
                        {{ $position->description }}
                    </p>
                @endif
            </div>
            
            <!-- Действия -->
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('permission-registry::positions.edit', $position) }}"
                   class="p-2 rounded-lg bg-white dark:bg-neutral-700 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-neutral-600 transition-colors"
                   title="{{ __('permission-registry::Edit') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </a>
                <button wire:click="confirmDelete({{ $position->id }})"
                        class="p-2 rounded-lg bg-white dark:bg-neutral-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-neutral-600 transition-colors"
                        title="{{ __('permission-registry::Delete') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Статистика -->
        <div class="flex flex-wrap gap-4 mt-4 ml-11">
            <div class="flex items-center gap-2 text-sm">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <span class="text-gray-700 dark:text-gray-300">
                    <span class="font-semibold">{{ $position->permissions_count ?? 0 }}</span>
                    {{ __('permission-registry::Permissions') }}
                </span>
            </div>
            
            <div class="flex items-center gap-2 text-sm">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30">
                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <span class="text-gray-700 dark:text-gray-300">
                    <span class="font-semibold">{{ $position->groups_count ?? 0 }}</span>
                    {{ __('permission-registry::Groups') }}
                </span>
            </div>
            
            <div class="flex items-center gap-2 text-sm">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/30">
                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
                <span class="text-gray-700 dark:text-gray-300">
                    <span class="font-semibold">{{ $position->users_count ?? 0 }}</span>
                    {{ __('permission-registry::Users') }}
                </span>
            </div>
        </div>
    </div>
    
    <!-- Дочерние позиции -->
    @if($hasChildren && $isOpen)
        <div class="children-container transition-all duration-300">
            @foreach($position->children as $child)
                @include('permission-registry::livewire.partials.position-card', [
                    'position' => $child,
                    'level' => $level + 1,
                    'openPositions' => $openPositions ?? []
                ])
            @endforeach
        </div>
    @endif
</div>
