<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                ⚡ {{ __('permission-registry::Triggers') }}: {{ $permission->name }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('permission-registry::permissions.edit', $permission) }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    {{ __('permission-registry::Back to Edit') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-xl sm:rounded-lg">
                @if(!empty($overlaps))
                    <div class="m-4 rounded-md border border-amber-200 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
                        <p class="text-sm font-semibold text-amber-900 dark:text-amber-300">
                            {{ __('permission-registry::messages.triggers_overlap_title') }}
                        </p>
                        <div class="mt-2 space-y-1">
                            @foreach($overlaps as $departmentId => $items)
                                <p class="text-sm text-amber-800 dark:text-amber-200">
                                    {{ __('permission-registry::messages.triggers_overlap_warning', [
                                        'department' => $departmentId,
                                        'permissions' => collect($items)->pluck('permission_name')->unique()->implode(', ')
                                    ]) }}
                                </p>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Tabs -->
                <div class="border-b border-gray-200 dark:border-neutral-700">
                    <nav class="flex -mb-px">
                        <button onclick="switchTab('grant')" id="tab-grant"
                                class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-purple-600 text-purple-600">
                            При выдаче ({{ $grantTriggers->count() }})
                        </button>
                        <button onclick="switchTab('revoke')" id="tab-revoke"
                                class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            При отзыве ({{ $revokeTriggers->count() }})
                        </button>
                    </nav>
                </div>

                <!-- Grant Triggers -->
                <div id="content-grant" class="tab-content p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Триггеры при выдаче права</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Эти триггеры выполняются последовательно при выдаче права. Измените порядок перетаскиванием.</p>
                    </div>

                    <div id="grant-triggers-list" class="space-y-2 mb-4">
                        @forelse($grantTriggers as $assignment)
                            <div class="trigger-item bg-gray-50 dark:bg-neutral-700 p-4 rounded-md flex items-center justify-between cursor-move border border-gray-200 dark:border-neutral-600"
                                 data-id="{{ $assignment->id }}">
                                <div class="flex items-center flex-1">
                                    <span class="text-gray-400 mr-3">☰</span>
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $assignment->trigger->name }}
                                            @if(in_array($assignment->trigger->id, $notConfiguredTriggerIds ?? []))
                                                <span class="text-amber-600 dark:text-amber-400 text-sm font-normal">— {{ __('permission-registry::Not configured') }}</span>
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $assignment->trigger->class_name }}</div>
                                        @if(!empty($assignment->config))
                                            <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                                @foreach($assignment->config as $key => $val)
                                                    @if($key === 'department_id')
                                                        {{ __('permission-registry::Department ID') }}: {{ $val }}
                                                    @else
                                                        {{ $key }}: {{ $val }}
                                                    @endif
                                                    @if(!$loop->last)<span class="mx-1">·</span>@endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-500">Порядок: {{ $assignment->order }}</span>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" class="toggle-trigger rounded text-purple-600"
                                               data-id="{{ $assignment->id }}"
                                               {{ $assignment->is_enabled ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Включен</span>
                                    </label>
                                    <button type="button" onclick="openConfigModal({{ $assignment->id }}, {{ $assignment->trigger->id }}, {{ json_encode($assignment->config ?? []) }})"
                                            class="text-sm px-2 py-1 text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300"
                                            title="{{ __('permission-registry::Trigger settings') }}">⚙️</button>
                                    <button onclick="removeTrigger({{ $assignment->id }})"
                                            class="text-red-600 hover:text-red-800">
                                        🗑️
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                Нет триггеров. Добавьте триггер ниже.
                            </div>
                        @endforelse
                    </div>

                    <div class="border-t border-gray-200 dark:border-neutral-700 pt-4">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Добавить триггер</h4>
                        <div class="flex flex-wrap gap-2 items-end">
                            <div class="flex-1 min-w-[200px]">
                                <select id="grant-trigger-select" class="w-full rounded-md border-gray-300 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-100">
                                    <option value="">Выберите триггер...</option>
                                    @foreach($availableTriggers as $trigger)
                                        @if($trigger->type === 'grant' || $trigger->type === 'both')
                                            @php($notConfigured = in_array($trigger->id, $notConfiguredTriggerIds ?? []))
                                            <option value="{{ $trigger->id }}" @if($notConfigured) disabled @endif>{{ $trigger->name }}@if($notConfigured) — {{ __('permission-registry::Not configured') }}@endif</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div id="grant-config-fields" class="hidden w-full mt-2 space-y-2 p-3 bg-gray-100 dark:bg-neutral-600 rounded-md"></div>
                            <button onclick="addTrigger('grant')"
                                    class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                                Добавить
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Revoke Triggers -->
                <div id="content-revoke" class="tab-content p-6 hidden">
                    <div class="mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Триггеры при отзыве права</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Эти триггеры выполняются последовательно при отзыве права.</p>
                    </div>

                    <div id="revoke-triggers-list" class="space-y-2 mb-4">
                        @forelse($revokeTriggers as $assignment)
                            <div class="trigger-item bg-gray-50 dark:bg-neutral-700 p-4 rounded-md flex items-center justify-between cursor-move border border-gray-200 dark:border-neutral-600"
                                 data-id="{{ $assignment->id }}">
                                <div class="flex items-center flex-1">
                                    <span class="text-gray-400 mr-3">☰</span>
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $assignment->trigger->name }}
                                            @if(in_array($assignment->trigger->id, $notConfiguredTriggerIds ?? []))
                                                <span class="text-amber-600 dark:text-amber-400 text-sm font-normal">— {{ __('permission-registry::Not configured') }}</span>
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $assignment->trigger->class_name }}</div>
                                        @if(!empty($assignment->config))
                                            <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                                @foreach($assignment->config as $key => $val)
                                                    @if($key === 'department_id')
                                                        {{ __('permission-registry::Department ID') }}: {{ $val }}
                                                    @else
                                                        {{ $key }}: {{ $val }}
                                                    @endif
                                                    @if(!$loop->last)<span class="mx-1">·</span>@endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-500">Порядок: {{ $assignment->order }}</span>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" class="toggle-trigger rounded text-purple-600"
                                               data-id="{{ $assignment->id }}"
                                               {{ $assignment->is_enabled ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Включен</span>
                                    </label>
                                    <button type="button" onclick="openConfigModal({{ $assignment->id }}, {{ $assignment->trigger->id }}, {{ json_encode($assignment->config ?? []) }})"
                                            class="text-sm px-2 py-1 text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300"
                                            title="{{ __('permission-registry::Trigger settings') }}">⚙️</button>
                                    <button onclick="removeTrigger({{ $assignment->id }})"
                                            class="text-red-600 hover:text-red-800">
                                        🗑️
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                Нет триггеров. Добавьте триггер ниже.
                            </div>
                        @endforelse
                    </div>

                    <div class="border-t border-gray-200 dark:border-neutral-700 pt-4">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Добавить триггер</h4>
                        <div class="flex flex-wrap gap-2 items-end">
                            <div class="flex-1 min-w-[200px]">
                                <select id="revoke-trigger-select" class="w-full rounded-md border-gray-300 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-100">
                                    <option value="">Выберите триггер...</option>
                                    @foreach($availableTriggers as $trigger)
                                        @if($trigger->type === 'revoke' || $trigger->type === 'both')
                                            @php($notConfigured = in_array($trigger->id, $notConfiguredTriggerIds ?? []))
                                            <option value="{{ $trigger->id }}" @if($notConfigured) disabled @endif>{{ $trigger->name }}@if($notConfigured) — {{ __('permission-registry::Not configured') }}@endif</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div id="revoke-config-fields" class="hidden w-full mt-2 space-y-2 p-3 bg-gray-100 dark:bg-neutral-600 rounded-md"></div>
                            <button onclick="addTrigger('revoke')"
                                    class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                                Добавить
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: редактирование настроек триггера -->
    <div id="config-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" onclick="closeConfigModal()"></div>
            <div class="relative bg-white dark:bg-neutral-800 rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('permission-registry::Trigger settings') }}</h3>
                <div id="config-modal-fields" class="space-y-3 mb-4"></div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeConfigModal()" class="px-3 py-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-neutral-700 rounded">{{ __('permission-registry::Cancel') }}</button>
                    <button type="button" onclick="saveConfigModal()" class="px-3 py-1.5 bg-purple-600 text-white rounded hover:bg-purple-700">{{ __('permission-registry::Save') }}</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const permissionId = {{ $permission->id }};
        let currentTab = 'grant';
        let currentConfigFields = { grant: [], revoke: [] };
        let configModalAssignmentId = null;

        function switchTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('border-purple-600', 'text-purple-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            document.getElementById('tab-' + tab).classList.add('border-purple-600', 'text-purple-600');
            document.getElementById('tab-' + tab).classList.remove('border-transparent', 'text-gray-500');
            document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
            document.getElementById('content-' + tab).classList.remove('hidden');
        }

        async function loadConfigFields(eventType, triggerId) {
            if (!triggerId) {
                document.getElementById(eventType + '-config-fields').classList.add('hidden');
                document.getElementById(eventType + '-config-fields').innerHTML = '';
                currentConfigFields[eventType] = [];
                return;
            }
            try {
                const r = await fetch(`/permission-registry/permissions/${permissionId}/triggers/config-fields/${triggerId}`, {
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                const data = await r.json();
                const fields = data.config_fields || [];
                currentConfigFields[eventType] = fields;
                const container = document.getElementById(eventType + '-config-fields');
                container.innerHTML = '';
                if (fields.length === 0) {
                    container.classList.add('hidden');
                    return;
                }
                container.classList.remove('hidden');
                fields.forEach(f => {
                    const label = document.createElement('label');
                    label.className = 'block text-sm font-medium text-gray-700 dark:text-gray-300';
                    label.textContent = (f.description || f.name) + (f.required ? ' *' : '');
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.name = 'config[' + f.name + ']';
                    input.dataset.configKey = f.name;
                    input.className = 'mt-1 block w-full rounded-md border-gray-300 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-100';
                    input.required = !!f.required;
                    label.appendChild(input);
                    container.appendChild(label);
                });
            } catch (e) {
                console.error(e);
                currentConfigFields[eventType] = [];
                document.getElementById(eventType + '-config-fields').classList.add('hidden');
            }
        }

        document.getElementById('grant-trigger-select').addEventListener('change', function() {
            loadConfigFields('grant', this.value);
        });
        document.getElementById('revoke-trigger-select').addEventListener('change', function() {
            loadConfigFields('revoke', this.value);
        });

        async function addTrigger(eventType) {
            const select = document.getElementById(eventType + '-trigger-select');
            const triggerId = select.value;

            if (!triggerId) {
                alert('Выберите триггер');
                return;
            }

            const config = {};
            const container = document.getElementById(eventType + '-config-fields');
            container.querySelectorAll('input[data-config-key]').forEach(input => {
                config[input.dataset.configKey] = input.value.trim();
            });

            try {
                const response = await fetch(`/permission-registry/permissions/${permissionId}/triggers`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        permission_trigger_id: triggerId,
                        event_type: eventType,
                        order: 999,
                        is_enabled: true,
                        config: config
                    })
                });

                if (response.ok) {
                    location.reload();
                } else {
                    const err = await response.json();
                    alert(err.message || 'Ошибка при добавлении триггера');
                }
            } catch (error) {
                console.error(error);
                alert('Ошибка при добавлении триггера');
            }
        }

        async function removeTrigger(assignmentId) {
            if (!confirm('Удалить триггер?')) {
                return;
            }

            try {
                const response = await fetch(`/permission-registry/permissions/${permissionId}/triggers/${assignmentId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    location.reload();
                } else {
                    alert('Ошибка при удалении триггера');
                }
            } catch (error) {
                console.error(error);
                alert('Ошибка при удалении триггера');
            }
        }

        function openConfigModal(assignmentId, triggerId, currentConfig) {
            configModalAssignmentId = assignmentId;
            document.getElementById('config-modal').classList.remove('hidden');
            const container = document.getElementById('config-modal-fields');
            container.innerHTML = '';
            fetch(`/permission-registry/permissions/${permissionId}/triggers/config-fields/${triggerId}`, {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            })
                .then(r => r.json())
                .then(data => {
                    const fields = data.config_fields || [];
                    fields.forEach(f => {
                        const label = document.createElement('label');
                        label.className = 'block text-sm font-medium text-gray-700 dark:text-gray-300';
                        label.textContent = (f.description || f.name) + (f.required ? ' *' : '');
                        const input = document.createElement('input');
                        input.type = 'text';
                        input.dataset.configKey = f.name;
                        input.value = (currentConfig && currentConfig[f.name]) || '';
                        input.className = 'mt-1 block w-full rounded-md border-gray-300 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-100';
                        input.required = !!f.required;
                        label.appendChild(input);
                        container.appendChild(label);
                    });
                })
                .catch(e => { console.error(e); closeConfigModal(); });
        }

        function closeConfigModal() {
            document.getElementById('config-modal').classList.add('hidden');
            configModalAssignmentId = null;
        }

        async function saveConfigModal() {
            if (!configModalAssignmentId) return;
            const config = {};
            document.getElementById('config-modal-fields').querySelectorAll('input[data-config-key]').forEach(input => {
                config[input.dataset.configKey] = input.value.trim();
            });
            try {
                const response = await fetch(`/permission-registry/permissions/${permissionId}/triggers/${configModalAssignmentId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ config: config })
                });
                if (response.ok) {
                    location.reload();
                } else {
                    alert('Ошибка сохранения');
                }
            } catch (e) {
                console.error(e);
                alert('Ошибка сохранения');
            }
        }

        document.querySelectorAll('.toggle-trigger').forEach(checkbox => {
            checkbox.addEventListener('change', async function() {
                const assignmentId = this.dataset.id;
                const isEnabled = this.checked;
                try {
                    await fetch(`/permission-registry/permissions/${permissionId}/triggers/${assignmentId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ is_enabled: isEnabled })
                    });
                } catch (error) {
                    console.error(error);
                    this.checked = !isEnabled;
                }
            });
        });
    </script>
</x-app-layout>
