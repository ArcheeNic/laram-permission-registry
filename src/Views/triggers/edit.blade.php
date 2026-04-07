<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Редактировать триггер: {{ $permissionTrigger->name }}
            </h2>
            <a href="{{ route('permission-registry::triggers.index') }}"
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                Назад к триггерам
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Основная форма редактирования -->
            <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                <form action="{{ route('permission-registry::triggers.update', $permissionTrigger) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-6">
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Название *
                            <x-perm::field-hint
                                :title="__('permission-registry::hints.triggers_name_title')"
                                :description="__('permission-registry::hints.triggers_name_desc')"
                            />
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name', $permissionTrigger->name) }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                        @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="class_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Класс (FQCN) *
                            <x-perm::field-hint
                                :title="__('permission-registry::hints.triggers_class_name_title')"
                                :description="__('permission-registry::hints.triggers_class_name_desc')"
                            />
                        </label>
                        <input type="text" name="class_name" id="class_name" value="{{ old('class_name', $permissionTrigger->class_name) }}" required readonly
                               placeholder="App\Triggers\ExampleTrigger"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 font-mono text-sm bg-gray-50 dark:bg-neutral-900">
                        @error('class_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Описание
                            <x-perm::field-hint
                                :title="__('permission-registry::hints.triggers_description_title')"
                                :description="__('permission-registry::hints.triggers_description_desc')"
                            />
                        </label>
                        <textarea name="description" id="description" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">{{ old('description', $permissionTrigger->description) }}</textarea>
                        @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Тип *
                            <x-perm::field-hint
                                :title="__('permission-registry::hints.triggers_type_title')"
                                :description="__('permission-registry::hints.triggers_type_desc')"
                            />
                        </label>
                        <select name="type" id="type" required
                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                            <option value="grant" {{ old('type', $permissionTrigger->type) === 'grant' ? 'selected' : '' }}>Grant (при выдаче)</option>
                            <option value="revoke" {{ old('type', $permissionTrigger->type) === 'revoke' ? 'selected' : '' }}>Revoke (при отзыве)</option>
                            <option value="both" {{ old('type', $permissionTrigger->type) === 'both' ? 'selected' : '' }}>Both (оба)</option>
                        </select>
                        @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $permissionTrigger->is_active) ? 'checked' : '' }}
                                   class="rounded text-purple-600 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                Активен
                                <x-perm::field-hint
                                    :title="__('permission-registry::hints.triggers_is_active_title')"
                                    :description="__('permission-registry::hints.triggers_is_active_desc')"
                                />
                            </span>
                        </label>
                    </div>

                    <!-- Маппинг полей триггера -->
                    @if(isset($metadata['required_fields']) && count($metadata['required_fields']) > 0)
                        <div class="mt-8 pt-8 border-t border-gray-200 dark:border-neutral-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                Маппинг полей триггера
                                <x-perm::field-hint
                                    :title="__('permission-registry::hints.triggers_mapping_title')"
                                    :description="__('permission-registry::hints.triggers_mapping_desc')"
                                />
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                                Свяжите поля триггера с глобальными полями из Permission Registry
                            </p>

                            @php
                                $inputFields = collect($metadata['required_fields'])->filter(fn($f) => !($f['is_internal'] ?? false));
                                $internalFields = collect($metadata['required_fields'])->filter(fn($f) => $f['is_internal'] ?? false);
                            @endphp

                            <!-- Входящие поля -->
                            @if($inputFields->isNotEmpty())
                                <div class="mb-8">
                                    <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-3">
                                        Входящие поля
                                    </h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                        Обязательные поля при заполнении
                                    </p>
                                    <div class="space-y-4">
                                        @foreach($inputFields as $field)
                                            <div class="border border-gray-200 dark:border-neutral-700 rounded-lg p-4 hover:border-purple-300 dark:hover:border-purple-700 transition-all">
                                                <div class="flex items-start justify-between mb-3">
                                                    <div class="flex-1">
                                                        <div class="flex items-center">
                                                            <span class="font-mono font-semibold text-gray-900 dark:text-gray-100 text-base">
                                                                {{ $field['name'] }}
                                                            </span>
                                                            <span class="ml-3 px-2.5 py-1 {{ $field['required'] ? 'bg-gradient-to-r from-red-500 to-pink-500 text-white' : 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' }} rounded-full text-xs font-medium shadow-sm">
                                                                {{ $field['required'] ? 'Обязательное' : 'Опциональное' }}
                                                            </span>
                                                        </div>
                                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                                            {{ $field['description'] }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                        Связать с глобальным полем
                                                    </label>
                                                    <select name="mapping[{{ $field['name'] }}]" required
                                                            class="block w-full rounded-lg border-gray-300 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-300 shadow-sm focus:border-purple-500 focus:ring-2 focus:ring-purple-500 focus:ring-opacity-50 transition-all {{ $errors->has("mapping.{$field['name']}") ? 'border-red-500' : '' }}">
                                                        <option value="">Выберите поле</option>
                                                        @foreach($globalFields as $globalField)
                                                            <option value="{{ $globalField->name }}" 
                                                                    {{ (($currentMapping[$field['name']]['permission_field_name'] ?? '') === $globalField->name) ? 'selected' : '' }}>
                                                                {{ $globalField->name }}
                                                                @if($globalField->description)
                                                                    - {{ $globalField->description }}
                                                                @endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error("mapping.{$field['name']}")
                                                        <p class="error-message mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                                    @enderror
                                                    @if(isset($currentMapping[$field['name']]))
                                                        <div class="mt-2 flex items-center text-xs text-green-600 dark:text-green-400">
                                                            <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                            </svg>
                                                            <span>Связано с: <code class="bg-green-50 dark:bg-green-900/30 px-1.5 py-0.5 rounded font-mono">{{ $currentMapping[$field['name']]['permission_field_name'] ?? '' }}</code></span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Внутренние поля -->
                            @if($internalFields->isNotEmpty())
                                <div>
                                    <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-3">
                                        Внутренние поля
                                    </h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                        Поля для внутренней обработки триггером
                                    </p>
                                    <div class="space-y-4">
                                        @foreach($internalFields as $field)
                                            <div class="border border-gray-200 dark:border-neutral-700 rounded-lg p-4 hover:border-purple-300 dark:hover:border-purple-700 transition-all">
                                                <div class="flex items-start justify-between mb-3">
                                                    <div class="flex-1">
                                                        <div class="flex items-center">
                                                            <span class="font-mono font-semibold text-gray-900 dark:text-gray-100 text-base">
                                                                {{ $field['name'] }}
                                                            </span>
                                                            <span class="ml-3 px-2.5 py-1 {{ $field['required'] ? 'bg-gradient-to-r from-red-500 to-pink-500 text-white' : 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' }} rounded-full text-xs font-medium shadow-sm">
                                                                {{ $field['required'] ? 'Обязательное' : 'Опциональное' }}
                                                            </span>
                                                        </div>
                                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                                            {{ $field['description'] }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                        Связать с глобальным полем
                                                    </label>
                                                    <select name="internal_mapping[{{ $field['name'] }}]" required
                                                            class="block w-full rounded-lg border-gray-300 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-300 shadow-sm focus:border-purple-500 focus:ring-2 focus:ring-purple-500 focus:ring-opacity-50 transition-all {{ $errors->has("internal_mapping.{$field['name']}") ? 'border-red-500' : '' }}">
                                                        <option value="">Выберите поле</option>
                                                        @foreach($globalFields as $globalField)
                                                            <option value="{{ $globalField->name }}" 
                                                                    {{ (($currentMapping[$field['name']]['permission_field_name'] ?? '') === $globalField->name) ? 'selected' : '' }}>
                                                                {{ $globalField->name }}
                                                                @if($globalField->description)
                                                                    - {{ $globalField->description }}
                                                                @endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error("internal_mapping.{$field['name']}")
                                                        <p class="error-message mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                                    @enderror
                                                    @if(isset($currentMapping[$field['name']]))
                                                        <div class="mt-2 flex items-center text-xs text-green-600 dark:text-green-400">
                                                            <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                            </svg>
                                                            <span>Связано с: <code class="bg-green-50 dark:bg-green-900/30 px-1.5 py-0.5 rounded font-mono">{{ $currentMapping[$field['name']]['permission_field_name'] ?? '' }}</code></span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="flex justify-end space-x-2 mt-6">
                        <button type="submit"
                                class="px-6 py-3 text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-pink-600 rounded-lg hover:shadow-lg hover:scale-105 transition-all duration-200 transform flex items-center space-x-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Сохранить</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Валидация формы при отправке
            const form = document.querySelector('form');
            
            form.addEventListener('submit', function(e) {
                let hasErrors = false;
                const selects = form.querySelectorAll('select[name^="mapping"], select[name^="internal_mapping"]');
                
                selects.forEach(select => {
                    const errorDiv = select.parentElement.querySelector('.error-message');
                    if (errorDiv) errorDiv.remove();
                    
                    if (!select.value) {
                        hasErrors = true;
                        select.classList.add('border-red-500');
                        const error = document.createElement('p');
                        error.className = 'error-message mt-1 text-sm text-red-600 dark:text-red-400';
                        error.textContent = 'Это поле обязательно для маппинга';
                        select.parentElement.appendChild(error);
                    } else {
                        select.classList.remove('border-red-500');
                    }
                });
                
                if (hasErrors) {
                    e.preventDefault();
                    return false;
                }
            });

            // Обработчик изменения для удаления ошибок
            const selects = form.querySelectorAll('select[name^="mapping"], select[name^="internal_mapping"]');
            selects.forEach(select => {
                select.addEventListener('change', function() {
                    if (this.value) {
                        this.classList.remove('border-red-500');
                        const errorDiv = this.parentElement.querySelector('.error-message');
                        if (errorDiv) errorDiv.remove();
                    }
                });
            });
        });
    </script>
</x-app-layout>
