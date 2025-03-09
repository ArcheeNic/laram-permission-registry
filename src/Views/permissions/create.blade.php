<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('permission-registry::Create Permission') }}
            </h2>
            <a href="{{ route('permission-registry::permissions.index') }}"
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                {{ __('permission-registry::Back to Permissions') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <form action="{{ route('permission-registry::permissions.store') }}" method="POST">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="service" class="block text-sm font-medium text-gray-700">
                                {{ __('permission-registry::Service') }} *
                            </label>
                            <input type="text" name="service" id="service" value="{{ old('service') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('service')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                {{ __('permission-registry::Name') }} *
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="description" class="block text-sm font-medium text-gray-700">
                            {{ __('permission-registry::Description') }}
                        </label>
                        <textarea name="description" id="description" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
                        @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('permission-registry::Tags') }}
                        </label>
                        <div class="flex flex-wrap gap-2 p-3 bg-gray-50 rounded-md" id="tags-container">
                            <input type="text" id="tag-input"
                                   class="p-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                                   placeholder="{{ __('permission-registry::Enter tag and press Enter') }}">
                        </div>
                        <div id="tags-hidden-inputs"></div>
                        @error('tags')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('permission-registry::Fields') }}
                        </label>
                        <div class="bg-gray-50 p-3 rounded-md">
                            @if($fields->isEmpty())
                                <p class="text-sm text-gray-500">{{ __('permission-registry::No fields available. Create fields first.') }}</p>
                            @else
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($fields as $field)
                                        <div class="flex items-center">
                                            <input type="checkbox" id="field_{{ $field->id }}" name="fields[]" value="{{ $field->id }}"
                                                   class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                {{ in_array($field->id, old('fields', [])) ? 'checked' : '' }}>
                                            <label for="field_{{ $field->id }}" class="ml-2 block text-sm text-gray-900">
                                                {{ $field->name }}
                                                @if($field->default_value)
                                                    <span class="text-gray-500">({{ __('permission-registry::Default') }}: {{ $field->default_value }})</span>
                                                @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @error('fields')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                            {{ __('permission-registry::Create Permission') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tagsContainer = document.getElementById('tags-container');
            const tagInput = document.getElementById('tag-input');
            const hiddenInputsContainer = document.getElementById('tags-hidden-inputs');
            let tags = @json(old('tags', []));

            // Инициализация тегов
            renderTags();

            // Обработка ввода тегов
            tagInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    const tag = tagInput.value.trim();
                    if (tag && !tags.includes(tag)) {
                        tags.push(tag);
                        renderTags();
                    }
                    tagInput.value = '';
                }
            });

            // Отображение тегов
            function renderTags() {
                // Очистка скрытых инпутов
                hiddenInputsContainer.innerHTML = '';

                // Добавление элементов DOM для каждого тега
                tags.forEach((tag, index) => {
                    // Создание скрытого инпута для отправки с формой
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = `tags[${index}]`;
                    hiddenInput.value = tag;
                    hiddenInputsContainer.appendChild(hiddenInput);
                });

                // Обновление отображения тегов
                const tagElements = tags.map(tag => {
                    return `<div class="bg-blue-100 text-blue-800 rounded px-2 py-1 text-sm flex items-center">
                        <span>${tag}</span>
                        <button type="button" class="ml-1 text-blue-500 hover:text-blue-700"
                                onclick="removeTag('${tag}')">×</button>
                    </div>`;
                }).join('');

                tagsContainer.innerHTML = tagElements + tagsContainer.innerHTML;

                // Перемещение поля ввода в конец контейнера
                tagsContainer.appendChild(tagInput);
            }

            // Функция удаления тега
            window.removeTag = function(tag) {
                tags = tags.filter(t => t !== tag);
                renderTags();
            };
        });
    </script>
</x-app-layout>
