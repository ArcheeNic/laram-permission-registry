<div class="space-y-6">
    <!-- Панель поиска и фильтров -->
    <div class="bg-white dark:bg-neutral-800 rounded-lg shadow-md p-6">
        <div class="flex flex-col sm:flex-row justify-between gap-4">
            <div class="flex-1">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input 
                        wire:model.live="search" 
                        type="text" 
                        placeholder="{{ __('permission-registry::Search positions') }}"
                        class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 dark:border-neutral-600 rounded-lg leading-5 bg-white dark:bg-neutral-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                    >
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('permission-registry::Show') }}:</span>
                <select 
                    wire:model.live="perPage" 
                    class="rounded-lg border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                >
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Уведомление об успехе -->
    @if (session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 text-green-700 dark:text-green-400 p-4 rounded-lg shadow-md animate-fade-in" role="alert">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <p class="font-medium">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <!-- Список должностей -->
    <div class="positions-container">
        @if($positions->count() > 0)
            @foreach($positions as $position)
                @include('permission-registry::livewire.partials.position-card', [
                    'position' => $position,
                    'level' => 0,
                    'openPositions' => $openPositions
                ])
            @endforeach
            
            <!-- Пагинация -->
            <div class="mt-6 bg-white dark:bg-neutral-800 rounded-lg shadow-md p-4">
                {{ $positions->links() }}
            </div>
        @else
            <!-- Пустое состояние -->
            <div class="bg-white dark:bg-neutral-800 rounded-lg shadow-md p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    {{ __('permission-registry::No positions found') }}
                </h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">
                    {{ $search ? __('permission-registry::Try adjusting your search criteria') : __('permission-registry::Get started by creating a new position') }}
                </p>
                @if(!$search)
                    <a href="{{ route('permission-registry::positions.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        {{ __('permission-registry::Create Position') }}
                    </a>
                @endif
            </div>
        @endif
    </div>

    <!-- Модальное окно подтверждения удаления -->
    @if($confirmingDelete)
        <div class="fixed inset-0 bg-gray-900/75 dark:bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-fade-in">
            <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-2xl max-w-md w-full transform transition-all animate-scale-in">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900/30">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white text-center mb-2">
                        {{ __('permission-registry::Confirm Delete') }}
                    </h3>
                    
                    <p class="text-sm text-gray-600 dark:text-gray-300 text-center mb-6">
                        {{ __('permission-registry::Are you sure you want to delete this position? This action cannot be undone.') }}
                    </p>
                </div>
                
                <div class="bg-gray-50 dark:bg-neutral-900/50 px-6 py-4 flex flex-col-reverse sm:flex-row justify-end gap-3 rounded-b-xl">
                    <button 
                        wire:click="cancelDelete" 
                        type="button"
                        class="w-full sm:w-auto px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-neutral-700 border border-gray-300 dark:border-neutral-600 rounded-lg hover:bg-gray-50 dark:hover:bg-neutral-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
                    >
                        {{ __('permission-registry::Cancel') }}
                    </button>
                    <button 
                        wire:click="deletePosition" 
                        type="button"
                        class="w-full sm:w-auto px-5 py-2.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors"
                    >
                        {{ __('permission-registry::Delete') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
