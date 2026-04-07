@props(['user'])

<div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-700 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('permission-registry::Groups') }}</h4>
    </div>

    <div class="mb-4">
        @if($user->groups->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                @foreach($user->groups as $group)
                    <div class="inline-flex items-center px-3 py-2 bg-gradient-to-r from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 text-purple-800 dark:text-purple-300 rounded-lg text-sm font-medium border border-purple-200 dark:border-purple-800 transition-all duration-200 hover:scale-105">
                        <span>{{ $group->name }}</span>
                        <button wire:click="removeGroup({{ $group->id }})"
                                class="ml-2 text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-200 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400 text-sm">{{ __('permission-registry::No groups assigned') }}</p>
        @endif
    </div>

    <div class="flex items-end space-x-3">
        <div class="flex-grow">
            <label for="group" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('permission-registry::Assign Group') }}
                <x-perm::field-hint
                    :title="__('permission-registry::hints.groups_section_selected_group_title')"
                    :description="__('permission-registry::hints.groups_section_selected_group_desc')"
                />
            </label>
            <select id="group" wire:model.live="selectedGroup"
                    class="block w-full rounded-lg border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <option value="">{{ __('permission-registry::Select a group') }}</option>
                @foreach($this->groups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                @endforeach
            </select>
        </div>
        <button wire:click="assignGroup"
                class="px-4 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 transform hover:scale-105"
                @if(!$this->selectedGroup) disabled @endif>
            {{ __('permission-registry::Assign') }}
        </button>
    </div>
</div>
