<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Создать триггер
            </h2>
            <a href="{{ route('permission-registry::triggers.index') }}"
               class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-700 bg-white dark:bg-neutral-700 border border-gray-300 dark:border-neutral-600 rounded-md hover:bg-gray-50 dark:hover:bg-neutral-600">
                Назад к триггерам
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                <form action="{{ route('permission-registry::triggers.store') }}" method="POST" id="triggerForm">
                    @csrf

                    <div class="mb-6">
                        <label for="class_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Выберите триггер *
                            <x-perm::field-hint
                                :title="__('permission-registry::hints.triggers_class_name_title')"
                                :description="__('permission-registry::hints.triggers_class_name_desc')"
                            />
                        </label>
                        <select name="class_name" id="class_name" required
                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 font-mono text-sm">
                            <option value="">Загрузка...</option>
                        </select>
                        @error('class_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Название *
                            <x-perm::field-hint
                                :title="__('permission-registry::hints.triggers_name_title')"
                                :description="__('permission-registry::hints.triggers_name_desc')"
                            />
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required readonly
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 bg-gray-50 dark:bg-neutral-900">
                        @error('name')
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
                        <textarea name="description" id="description" rows="3" readonly
                                  class="mt-1 block w-full rounded-md border-gray-300 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 bg-gray-50 dark:bg-neutral-900">{{ old('description') }}</textarea>
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
                            <option value="grant" {{ old('type') === 'grant' ? 'selected' : '' }}>Grant (при выдаче)</option>
                            <option value="revoke" {{ old('type') === 'revoke' ? 'selected' : '' }}>Revoke (при отзыве)</option>
                            <option value="both" {{ old('type', 'both') === 'both' ? 'selected' : '' }}>Both (оба)</option>
                        </select>
                        @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
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
                    <div id="mappingFieldsSection" class="mb-6 hidden">
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

                            <!-- Входящие поля -->
                            <div id="inputFieldsSection" class="mb-8 hidden">
                                <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-3">
                                    Входящие поля
                                </h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                    Обязательные поля при заполнении
                                </p>
                                <div id="inputFieldsList" class="space-y-4">
                                </div>
                            </div>

                            <!-- Внутренние поля -->
                            <div id="internalFieldsSection" class="hidden">
                                <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-3">
                                    Внутренние поля
                                </h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                    Поля для внутренней обработки триггером
                                </p>
                                <div id="internalFieldsList" class="space-y-4">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="px-6 py-3 text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-pink-600 rounded-lg hover:shadow-lg hover:scale-105 transition-all duration-200 transform flex items-center space-x-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Создать триггер</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const classNameSelect = document.getElementById('class_name');
            const nameInput = document.getElementById('name');
            const descriptionInput = document.getElementById('description');
            const mappingFieldsSection = document.getElementById('mappingFieldsSection');
            const inputFieldsSection = document.getElementById('inputFieldsSection');
            const internalFieldsSection = document.getElementById('internalFieldsSection');
            const inputFieldsList = document.getElementById('inputFieldsList');
            const internalFieldsList = document.getElementById('internalFieldsList');

            // Глобальные поля из backend
            const globalFields = @json($globalFields);

            // Функция для создания элемента поля
            function createFieldElement(field, mappingType) {
                const fieldContainer = document.createElement('div');
                fieldContainer.className = 'border border-gray-200 dark:border-neutral-700 rounded-lg p-4 hover:border-purple-300 dark:hover:border-purple-700 transition-all';
                
                const headerDiv = document.createElement('div');
                headerDiv.className = 'flex items-start justify-between mb-3';
                
                const contentDiv = document.createElement('div');
                contentDiv.className = 'flex-1';
                
                const titleDiv = document.createElement('div');
                titleDiv.className = 'flex items-center';
                
                const fieldName = document.createElement('span');
                fieldName.className = 'font-mono font-semibold text-gray-900 dark:text-gray-100 text-base';
                fieldName.textContent = field.name;
                
                const badge = document.createElement('span');
                badge.className = field.required 
                    ? 'ml-3 px-2.5 py-1 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-full text-xs font-medium shadow-sm'
                    : 'ml-3 px-2.5 py-1 bg-gradient-to-r from-blue-500 to-cyan-500 text-white rounded-full text-xs font-medium shadow-sm';
                badge.textContent = field.required ? 'Обязательное' : 'Опциональное';
                
                const fieldDesc = document.createElement('p');
                fieldDesc.className = 'text-sm text-gray-600 dark:text-gray-400 mt-2';
                fieldDesc.textContent = field.description;
                
                titleDiv.appendChild(fieldName);
                titleDiv.appendChild(badge);
                contentDiv.appendChild(titleDiv);
                contentDiv.appendChild(fieldDesc);
                headerDiv.appendChild(contentDiv);
                
                const selectContainer = document.createElement('div');
                
                const label = document.createElement('label');
                label.className = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2';
                label.textContent = 'Связать с глобальным полем';
                
                const select = document.createElement('select');
                select.name = mappingType + '[' + field.name + ']';
                select.required = true;
                const errorKey = mappingType + '.' + field.name;
                const hasError = @json($errors->keys()).includes(errorKey);
                select.className = 'block w-full rounded-lg border-gray-300 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-300 shadow-sm focus:border-purple-500 focus:ring-2 focus:ring-purple-500 focus:ring-opacity-50 transition-all' + (hasError ? ' border-red-500' : '');
                
                const emptyOption = document.createElement('option');
                emptyOption.value = '';
                emptyOption.textContent = 'Выберите поле';
                select.appendChild(emptyOption);
                
                globalFields.forEach(globalField => {
                    const option = document.createElement('option');
                    option.value = globalField.name;
                    option.textContent = globalField.name + (globalField.description ? ' - ' + globalField.description : '');
                    select.appendChild(option);
                });
                
                // Обработчик изменения для удаления ошибок
                select.addEventListener('change', function() {
                    if (this.value) {
                        this.classList.remove('border-red-500');
                        const errorDiv = this.parentElement.querySelector('.error-message');
                        if (errorDiv) errorDiv.remove();
                    }
                });
                
                selectContainer.appendChild(label);
                selectContainer.appendChild(select);
                
                // Добавить серверную ошибку, если есть
                const errors = @json($errors->messages());
                if (errors[errorKey]) {
                    const errorP = document.createElement('p');
                    errorP.className = 'error-message mt-1 text-sm text-red-600 dark:text-red-400';
                    errorP.textContent = errors[errorKey][0];
                    selectContainer.appendChild(errorP);
                }
                
                fieldContainer.appendChild(headerDiv);
                fieldContainer.appendChild(selectContainer);
                
                return fieldContainer;
            }

            // Загрузить список триггеров
            fetch('{{ route('permission-registry::triggers.api.discover') }}')
                .then(response => response.json())
                .then(data => {
                    classNameSelect.innerHTML = '<option value="">Выберите триггер</option>';
                    
                    if (data.success && data.triggers.length > 0) {
                        data.triggers.forEach(trigger => {
                            const option = document.createElement('option');
                            option.value = trigger.class_name;
                            option.textContent = trigger.name;
                            option.dataset.metadata = JSON.stringify(trigger);
                            classNameSelect.appendChild(option);
                        });
                    } else {
                        classNameSelect.innerHTML = '<option value="">Триггеры не найдены</option>';
                    }
                })
                .catch(error => {
                    console.error('Ошибка загрузки триггеров:', error);
                    classNameSelect.innerHTML = '<option value="">Ошибка загрузки</option>';
                });

            // Обработчик выбора триггера
            classNameSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                
                if (selectedOption.dataset.metadata) {
                    const metadata = JSON.parse(selectedOption.dataset.metadata);
                    
                    // Заполнить поля
                    nameInput.value = metadata.name;
                    descriptionInput.value = metadata.description;
                    
                    // Отобразить маппинг полей
                    if (metadata.required_fields && metadata.required_fields.length > 0) {
                        inputFieldsList.innerHTML = '';
                        internalFieldsList.innerHTML = '';
                        
                        const inputFields = metadata.required_fields.filter(f => !f.is_internal);
                        const internalFields = metadata.required_fields.filter(f => f.is_internal);
                        
                        // Создать поля для входящих полей
                        inputFields.forEach(field => {
                            const fieldContainer = createFieldElement(field, 'mapping');
                            inputFieldsList.appendChild(fieldContainer);
                        });
                        
                        // Создать поля для внутренних полей
                        internalFields.forEach(field => {
                            const fieldContainer = createFieldElement(field, 'internal_mapping');
                            internalFieldsList.appendChild(fieldContainer);
                        });
                        
                        // Показать/скрыть секции
                        if (inputFields.length > 0) {
                            inputFieldsSection.classList.remove('hidden');
                        } else {
                            inputFieldsSection.classList.add('hidden');
                        }
                        
                        if (internalFields.length > 0) {
                            internalFieldsSection.classList.remove('hidden');
                        } else {
                            internalFieldsSection.classList.add('hidden');
                        }
                        
                        mappingFieldsSection.classList.remove('hidden');
                    } else {
                        mappingFieldsSection.classList.add('hidden');
                    }
                } else {
                    nameInput.value = '';
                    descriptionInput.value = '';
                    mappingFieldsSection.classList.add('hidden');
                }
            });

            // Валидация формы при отправке
            const form = document.getElementById('triggerForm');
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
        });
    </script>
</x-app-layout>
