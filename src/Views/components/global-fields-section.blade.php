<!-- Используется внутри Livewire компонента -->
<div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-700 p-5">
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center space-x-2">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('permission-registry::Global Fields') }}</h4>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                {{ $this->globalFieldDefinitions->count() }}
            </span>
        </div>
        <button wire:click="toggleGlobalFields"
                type="button"
                class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-neutral-700 border border-gray-300 dark:border-neutral-600 rounded-lg hover:bg-gray-200 dark:hover:bg-neutral-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
            <svg class="w-4 h-4 mr-1.5 transform transition-transform duration-200 {{ $this->showGlobalFields ? 'rotate-180' : '' }}" 
                 fill="none" 
                 stroke="currentColor" 
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
            <span>{{ $this->showGlobalFields ? __('permission-registry::Hide') : __('permission-registry::Show') }}</span>
        </button>
    </div>
    
    <div x-data="{ show: @entangle('showGlobalFields') }" 
         x-show="show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-2"
         style="display: none;">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            @foreach($this->globalFieldDefinitions as $field)
                <div class="group">
                    <label for="global_field_{{ $field->id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <span class="flex items-center">
                            {{ $field->name }}
                            @if($field->required_on_user_create)
                                <span class="ml-1 text-red-500 dark:text-red-400">*</span>
                            @endif
                            <x-perm::field-hint
                                :title="__('permission-registry::hints.global_fields_values_title')"
                                :description="__('permission-registry::hints.global_fields_values_desc')"
                            />
                        </span>
                    </label>
                    <input type="text" 
                           id="global_field_{{ $field->id }}"
                           wire:model="globalFields.{{ $field->id }}"
                           placeholder="{{ $field->default_value ?? '' }}"
                           class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 dark:text-gray-100 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 group-hover:border-gray-400 dark:group-hover:border-neutral-500">
                </div>
            @endforeach
        </div>
        <div class="mt-5 flex justify-end">
            <button wire:click="saveGlobalFields"
                    type="button"
                    class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 rounded-lg shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 transform hover:scale-105">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                {{ __('permission-registry::Save Global Fields') }}
            </button>
        </div>
    </div>
</div>
