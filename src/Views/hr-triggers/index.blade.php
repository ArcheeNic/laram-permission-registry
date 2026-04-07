<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('permission-registry::messages.hr_triggers') }}
            </h2>
            <a href="{{ route('permission-registry::index') }}"
               class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-neutral-700 border border-gray-300 dark:border-neutral-600 rounded-md hover:bg-gray-50 dark:hover:bg-neutral-600">
                {{ __('permission-registry::messages.back') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @php($categoryValues = array_map(static fn ($category) => $category->value, $categories))
            <div id="hr-trigger-messages"
                 data-select-trigger="{{ __('permission-registry::messages.select_trigger') }}"
                 data-add-trigger-failed="{{ __('permission-registry::messages.failed_to_add_trigger') }}"
                 data-delete-trigger-confirm="{{ __('permission-registry::messages.delete_trigger_confirm') }}"
                 data-delete-trigger-failed="{{ __('permission-registry::messages.failed_to_delete_trigger') }}"
                 data-categories="{{ implode(',', $categoryValues) }}"></div>
            <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="border-b border-gray-200 dark:border-neutral-700">
                    <nav class="flex -mb-px">
                        @foreach($categories as $category)
                            <button onclick="switchCategory('{{ $category->value }}')" id="category-tab-{{ $category->value }}"
                                    class="category-tab-button px-6 py-4 text-sm font-medium border-b-2 {{ $loop->first ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                {{ __($category->labelKey()) }}
                            </button>
                        @endforeach
                    </nav>
                </div>

                @foreach($categories as $category)
                    <div id="category-content-{{ $category->value }}" class="category-content p-6 {{ $loop->first ? '' : 'hidden' }}">
                        <div class="border-b border-gray-200 dark:border-neutral-700 mb-6">
                            <nav class="flex -mb-px">
                                <button
                                    onclick="switchEvent('{{ $category->value }}', 'hire')"
                                    id="event-tab-{{ $category->value }}-hire"
                                    class="event-tab-button-{{ $category->value }} px-6 py-4 text-sm font-medium border-b-2 border-purple-600 text-purple-600"
                                >
                                    {{ __('permission-registry::messages.on_hire') }}
                                    ({{ $triggersByCategory[$category->value]['hire']->count() }})
                                </button>
                                <button
                                    onclick="switchEvent('{{ $category->value }}', 'fire')"
                                    id="event-tab-{{ $category->value }}-fire"
                                    class="event-tab-button-{{ $category->value }} px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                                >
                                    {{ __('permission-registry::messages.on_fire') }}
                                    ({{ $triggersByCategory[$category->value]['fire']->count() }})
                                </button>
                            </nav>
                        </div>

                        <div id="event-content-{{ $category->value }}-hire" class="event-content-{{ $category->value }}">
                            @include('permission-registry::hr-triggers.partials.assignment-list', [
                                'key' => "{$category->value}-hire",
                                'employeeCategory' => $category->value,
                                'eventType' => 'hire',
                                'title' => __('permission-registry::messages.triggers_on_hire'),
                                'description' => __('permission-registry::messages.triggers_on_hire_description'),
                                'assignments' => $triggersByCategory[$category->value]['hire'],
                                'availableTriggers' => $availableTriggers,
                                'notConfiguredTriggerIds' => $notConfiguredTriggerIds,
                            ])
                        </div>

                        <div id="event-content-{{ $category->value }}-fire" class="event-content-{{ $category->value }} hidden">
                            @include('permission-registry::hr-triggers.partials.assignment-list', [
                                'key' => "{$category->value}-fire",
                                'employeeCategory' => $category->value,
                                'eventType' => 'fire',
                                'title' => __('permission-registry::messages.triggers_on_fire'),
                                'description' => __('permission-registry::messages.triggers_on_fire_description'),
                                'assignments' => $triggersByCategory[$category->value]['fire'],
                                'availableTriggers' => $availableTriggers,
                                'notConfiguredTriggerIds' => $notConfiguredTriggerIds,
                            ])
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <script>
        const messageElement = document.getElementById('hr-trigger-messages');
        const categories = (messageElement.dataset.categories || '').split(',').filter(Boolean);
        let currentCategory = categories[0] || 'staff';
        const selectTriggerMessage = messageElement.dataset.selectTrigger;
        const addTriggerFailedMessage = messageElement.dataset.addTriggerFailed;
        const deleteTriggerConfirmMessage = messageElement.dataset.deleteTriggerConfirm;
        const deleteTriggerFailedMessage = messageElement.dataset.deleteTriggerFailed;

        function switchCategory(category) {
            currentCategory = category;
            document.querySelectorAll('.category-tab-button').forEach(btn => {
                btn.classList.remove('border-purple-600', 'text-purple-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            document.getElementById('category-tab-' + category).classList.add('border-purple-600', 'text-purple-600');
            document.getElementById('category-tab-' + category).classList.remove('border-transparent', 'text-gray-500');

            document.querySelectorAll('.category-content').forEach(content => content.classList.add('hidden'));
            document.getElementById('category-content-' + category).classList.remove('hidden');
        }

        function switchEvent(category, eventType) {
            document.querySelectorAll('.event-tab-button-' + category).forEach(btn => {
                btn.classList.remove('border-purple-600', 'text-purple-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            document.getElementById('event-tab-' + category + '-' + eventType).classList.add('border-purple-600', 'text-purple-600');
            document.getElementById('event-tab-' + category + '-' + eventType).classList.remove('border-transparent', 'text-gray-500');

            document.querySelectorAll('.event-content-' + category).forEach(content => content.classList.add('hidden'));
            document.getElementById('event-content-' + category + '-' + eventType).classList.remove('hidden');
        }

        async function addTrigger(eventType, employeeCategory, key) {
            const select = document.getElementById(key + '-trigger-select');
            const triggerId = select.value;
            if (!triggerId) {
                alert(selectTriggerMessage);
                return;
            }

            const config = {};
            document.querySelectorAll('#' + key + '-config-fields input[data-config-key]').forEach(input => {
                config[input.dataset.configKey] = input.value.trim();
            });

            const response = await fetch(`{{ route('permission-registry::hr-triggers.store') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    permission_trigger_id: triggerId,
                    event_type: eventType,
                    employee_category: employeeCategory,
                    order: 999,
                    is_enabled: true,
                    config: config,
                }),
            });

            if (response.ok) {
                location.reload();
                return;
            }

            alert(addTriggerFailedMessage);
        }

        async function removeTrigger(assignmentId) {
            if (!confirm(deleteTriggerConfirmMessage)) {
                return;
            }

            const response = await fetch(`{{ url('/permission-registry/hr-triggers') }}/${assignmentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (response.ok) {
                location.reload();
                return;
            }

            alert(deleteTriggerFailedMessage);
        }

        document.querySelectorAll('.toggle-trigger').forEach(checkbox => {
            checkbox.addEventListener('change', async function() {
                const assignmentId = this.dataset.id;
                const isEnabled = this.checked;

                const response = await fetch(`{{ url('/permission-registry/hr-triggers') }}/${assignmentId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ is_enabled: isEnabled })
                });

                if (!response.ok) {
                    this.checked = !isEnabled;
                }
            });
        });

        document.querySelectorAll('.remove-trigger-btn').forEach(button => {
            button.addEventListener('click', function() {
                removeTrigger(this.dataset.id);
            });
        });

        async function loadConfigFields(key, triggerId) {
            const container = document.getElementById(key + '-config-fields');

            if (!triggerId) {
                container.classList.add('hidden');
                container.innerHTML = '';
                return;
            }

            try {
                const response = await fetch(`{{ url('/permission-registry/hr-triggers/config-fields') }}/${triggerId}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                if (!response.ok) {
                    throw new Error('Failed to load config fields');
                }

                const data = await response.json();
                const fields = data.config_fields || [];
                container.innerHTML = '';

                if (fields.length === 0) {
                    container.classList.add('hidden');
                    return;
                }

                container.classList.remove('hidden');
                fields.forEach(field => {
                    const label = document.createElement('label');
                    label.className = 'block text-sm font-medium text-gray-700 dark:text-gray-300';
                    label.textContent = (field.description || field.name) + (field.required ? ' *' : '');

                    const input = document.createElement('input');
                    input.type = 'text';
                    input.dataset.configKey = field.name;
                    input.required = !!field.required;
                    input.className = 'mt-1 block w-full rounded-md border-gray-300 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-100';

                    label.appendChild(input);
                    container.appendChild(label);
                });
            } catch (error) {
                container.classList.add('hidden');
                container.innerHTML = '';
                console.error(error);
            }
        }

        document.querySelectorAll('[data-trigger-select]').forEach((selectElement) => {
            selectElement.addEventListener('change', function() {
                loadConfigFields(this.dataset.key, this.value);
            });
        });
    </script>
</x-app-layout>
