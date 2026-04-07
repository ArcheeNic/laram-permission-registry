@props(['user'])

<div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-700 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
        </svg>
        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('permission-registry::Positions') }}</h4>
    </div>

    <div class="mb-4">
        @if($user->positions->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                @foreach($user->positions as $position)
                    <div class="inline-flex items-center px-3 py-2 bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 text-blue-800 dark:text-blue-300 rounded-lg text-sm font-medium border border-blue-200 dark:border-blue-800 transition-all duration-200 hover:scale-105">
                        <span>@include('permission-registry::components.position-hierarchy-label', ['position' => $position])</span>
                        <button wire:click="removePosition({{ $position->id }})"
                                class="ml-2 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400 text-sm">{{ __('permission-registry::No positions assigned') }}</p>
        @endif
    </div>

    <div class="flex items-end space-x-3">
        <div class="flex-grow">
            <label for="position" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('permission-registry::Add Position') }}
                <x-perm::field-hint
                    :title="__('permission-registry::hints.positions_section_selected_position_title')"
                    :description="__('permission-registry::hints.positions_section_selected_position_desc')"
                />
            </label>
            <select id="position" wire:model.live="selectedPosition"
                    class="block w-full rounded-lg border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <option value="">{{ __('permission-registry::Select a position') }}</option>
                @foreach($this->positions as $position)
                    <option value="{{ $position->id }}">{{ $position->hierarchyPathLabel() }}</option>
                @endforeach
            </select>
        </div>
        <button wire:click="assignPosition"
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 transform hover:scale-105"
                @if(!$this->selectedPosition) disabled @endif>
            {{ __('permission-registry::Add') }}
        </button>
    </div>
</div>
