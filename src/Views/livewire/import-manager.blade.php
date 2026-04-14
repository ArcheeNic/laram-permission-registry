<div>
    @if($flashMessage)
        <div class="mb-3 p-3 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded text-sm">
            {{ $flashMessage }}
        </div>
    @endif

    @if($flashError)
        <div class="mb-3 p-3 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded text-sm">
            {{ $flashError }}
        </div>
    @endif

    {{-- Step: List --}}
    @if($step === 'list')
        <div class="bg-white dark:bg-neutral-800 rounded-lg shadow">
            <div class="p-4 border-b dark:border-neutral-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('permission-registry::messages.import.title') }}
                </h3>
            </div>

            @if($imports->isEmpty())
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    {{ __('permission-registry::messages.import.no_imports') }}
                </div>
            @else
                {{-- Desktop table --}}
                <div class="hidden md:block">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                        <thead class="bg-gray-50 dark:bg-neutral-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ __('permission-registry::messages.import.name') }}
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ __('permission-registry::messages.import.description') }}
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ __('permission-registry::messages.status') }}
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ __('permission-registry::messages.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                            @foreach($imports as $import)
                                <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700/50">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $import->name }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $import->description ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                            {{ $import->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ $import->is_active ? __('permission-registry::messages.active') : __('permission-registry::messages.deactivated') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-2">
                                        <button wire:click="openSettings({{ $import->id }})"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md transition-colors
                                                       bg-gray-100 text-gray-700 hover:bg-gray-200
                                                       dark:bg-neutral-700 dark:text-gray-300 dark:hover:bg-neutral-600">
                                            <svg class="mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            {{ __('permission-registry::messages.import.settings') }}
                                        </button>
                                        <button wire:click="startImport({{ $import->id }})"
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md transition-colors
                                                       bg-indigo-100 text-indigo-700 hover:bg-indigo-200
                                                       dark:bg-indigo-900 dark:text-indigo-300 dark:hover:bg-indigo-800">
                                            <svg wire:loading wire:target="startImport({{ $import->id }})" class="animate-spin -ml-1 mr-1.5 h-3 w-3" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                            </svg>
                                            {{ __('permission-registry::messages.import.start') }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile cards --}}
                <div class="md:hidden divide-y divide-gray-200 dark:divide-neutral-700">
                    @foreach($imports as $import)
                        <div class="p-4 space-y-2">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $import->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $import->description ?? '—' }}</p>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $import->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $import->is_active ? __('permission-registry::messages.active') : __('permission-registry::messages.deactivated') }}
                                </span>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button wire:click="openSettings({{ $import->id }})"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md transition-colors
                                               bg-gray-100 text-gray-700 hover:bg-gray-200
                                               dark:bg-neutral-700 dark:text-gray-300 dark:hover:bg-neutral-600">
                                    {{ __('permission-registry::messages.import.settings') }}
                                </button>
                                <button wire:click="startImport({{ $import->id }})"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md transition-colors
                                               bg-indigo-100 text-indigo-700 hover:bg-indigo-200
                                               dark:bg-indigo-900 dark:text-indigo-300 dark:hover:bg-indigo-800">
                                    {{ __('permission-registry::messages.import.start') }}
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Execution history --}}
        @if($executionLogs->isNotEmpty())
            <div class="mt-6 bg-white dark:bg-neutral-800 rounded-lg shadow">
                <div class="p-4 border-b dark:border-neutral-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ __('permission-registry::messages.import.history_title') }}
                    </h3>
                </div>

                {{-- Desktop --}}
                <div class="hidden md:block">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                        <thead class="bg-gray-50 dark:bg-neutral-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ __('permission-registry::messages.import.name') }}
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ __('permission-registry::messages.status') }}
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ __('permission-registry::messages.import.started_at') }}
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ __('permission-registry::messages.import.completed_at') }}
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ __('permission-registry::messages.import.stats') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                            @foreach($executionLogs as $log)
                                @php
                                    $logStatusClasses = match($log->status?->value ?? $log->status) {
                                        'pending' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                        'running' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                                        'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                        'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                        default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                    };
                                @endphp
                                <tr wire:click="viewRun('{{ $log->import_run_id }}')" class="hover:bg-gray-50 dark:hover:bg-neutral-700/50 cursor-pointer">
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $log->permissionImport->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $logStatusClasses }}">
                                            {{ $log->status?->value ?? $log->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $log->started_at?->format('d.m.Y H:i') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $log->completed_at?->format('d.m.Y H:i') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                        @if($log->stats)
                                            <span class="text-xs">
                                                {{ __('permission-registry::messages.import.result_created') }}: {{ $log->stats['created'] ?? 0 }},
                                                {{ __('permission-registry::messages.import.result_updated') }}: {{ $log->stats['updated'] ?? 0 }},
                                                {{ __('permission-registry::messages.import.result_fired') }}: {{ $log->stats['fired'] ?? 0 }}
                                            </span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile cards --}}
                <div class="md:hidden divide-y divide-gray-200 dark:divide-neutral-700">
                    @foreach($executionLogs as $log)
                        @php
                            $logStatusClasses = match($log->status?->value ?? $log->status) {
                                'pending' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                'running' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                                'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                            };
                        @endphp
                        <div wire:click="viewRun('{{ $log->import_run_id }}')" class="p-4 space-y-2 cursor-pointer">
                            <div class="flex justify-between items-start">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $log->permissionImport->name ?? '—' }}
                                </p>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $logStatusClasses }}">
                                    {{ $log->status?->value ?? $log->status }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                                <p>{{ $log->started_at?->format('d.m.Y H:i') ?? '' }}</p>
                                @if($log->stats)
                                    <p>
                                        {{ __('permission-registry::messages.import.result_created') }}: {{ $log->stats['created'] ?? 0 }},
                                        {{ __('permission-registry::messages.import.result_updated') }}: {{ $log->stats['updated'] ?? 0 }},
                                        {{ __('permission-registry::messages.import.result_fired') }}: {{ $log->stats['fired'] ?? 0 }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    {{-- Step: Settings --}}
    @if($step === 'settings')
        <div class="bg-white dark:bg-neutral-800 rounded-lg shadow">
            <div class="p-4 border-b dark:border-neutral-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('permission-registry::messages.import.settings_title') }}
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('permission-registry::messages.import.settings_desc') }}
                </p>
            </div>

            <div class="p-6 space-y-6">
                {{-- Key field (internal / email) --}}
                <div class="p-4 rounded-lg border-2 border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-900/20">
                    <label class="block text-sm font-semibold text-indigo-700 dark:text-indigo-300 mb-2">
                        {{ __('permission-registry::messages.import.key_field') }}
                    </label>
                    <p class="text-xs text-indigo-600 dark:text-indigo-400 mb-3">
                        {{ __('permission-registry::messages.import.key_field_hint') }}
                    </p>
                    <select wire:model="internalFieldId"
                            class="block w-full rounded-md border-gray-300 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">{{ __('permission-registry::messages.import.select_field') }}</option>
                        @foreach($globalFields as $gf)
                            <option value="{{ $gf->id }}">{{ $gf->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Required fields mapping --}}
                @foreach($requiredFields as $field)
                    <div class="p-4 rounded-lg border border-gray-200 dark:border-neutral-700 hover:border-indigo-300 dark:hover:border-indigo-700 transition-colors">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">{{ $field['name'] }}</span>
                                @if($field['required'] ?? false)
                                    <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 rounded-full text-xs font-medium">
                                        {{ __('permission-registry::messages.import.required') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        @if(!empty($field['description']))
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">{{ $field['description'] }}</p>
                        @endif
                        <select wire:model="fieldMapping.{{ $field['name'] }}"
                                class="block w-full rounded-md border-gray-300 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">{{ __('permission-registry::messages.import.select_field') }}</option>
                            @foreach($globalFields as $gf)
                                <option value="{{ $gf->id }}">{{ $gf->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>

            <div class="p-4 border-t dark:border-neutral-700 flex flex-col sm:flex-row gap-2 sm:justify-end">
                <button wire:click="backToList"
                        class="w-full sm:w-auto px-4 py-2 text-sm font-medium rounded-md transition-colors
                               bg-white text-gray-700 border border-gray-300 hover:bg-gray-50
                               dark:bg-neutral-700 dark:text-gray-200 dark:border-neutral-600 dark:hover:bg-neutral-600">
                    {{ __('permission-registry::messages.import.cancel') }}
                </button>
                <button wire:click="saveMapping"
                        class="w-full sm:w-auto px-4 py-2 text-sm font-medium rounded-md transition-colors
                               bg-indigo-600 text-white hover:bg-indigo-700
                               dark:bg-indigo-500 dark:hover:bg-indigo-600">
                    {{ __('permission-registry::messages.import.save_mapping') }}
                </button>
            </div>
        </div>
    @endif

    {{-- Step: History View (read-only) --}}
    @if($step === 'history_view')
        <div class="bg-white dark:bg-neutral-800 rounded-lg shadow">
            <div class="p-4 border-b dark:border-neutral-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('permission-registry::messages.import.history_detail') }}
                </h3>
                <button wire:click="backToList"
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors
                               bg-gray-100 text-gray-700 hover:bg-gray-200
                               dark:bg-neutral-700 dark:text-gray-300 dark:hover:bg-neutral-600">
                    {{ __('permission-registry::messages.back') }}
                </button>
            </div>

            @include('permission-registry::livewire.partials.import-status-filter')

            {{-- Desktop table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                    <thead class="bg-gray-50 dark:bg-neutral-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::messages.import.first_name') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::messages.import.last_name') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::messages.status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::messages.import.action_column') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::messages.import.approved') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                        @foreach($stagingRows as $row)
                            @php $fields = is_array($row->fields) ? $row->fields : []; @endphp
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $fields['email'] ?? ($row->matchedVirtualUser->name ?? '—') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $fields['first_name'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $fields['last_name'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @include('permission-registry::livewire.partials.import-status-badge', ['row' => $row])
                                </td>
                                <td class="px-4 py-3">
                                    @include('permission-registry::livewire.partials.import-row-action', ['row' => $row, 'rowActions' => $rowActions])
                                </td>
                                <td class="px-4 py-3 text-sm text-center">
                                    @if($row->is_approved === true)
                                        <span class="text-green-600 dark:text-green-400">&#10003;</span>
                                    @elseif($row->is_approved === false)
                                        <span class="text-red-500 dark:text-red-400">&#10007;</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <div class="md:hidden divide-y divide-gray-200 dark:divide-neutral-700">
                @foreach($stagingRows as $row)
                    @php $fields = is_array($row->fields) ? $row->fields : []; @endphp
                    <div class="p-4 space-y-2">
                        <div class="flex justify-between items-start">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $fields['email'] ?? $row->external_id }}</p>
                            @include('permission-registry::livewire.partials.import-status-badge', ['row' => $row])
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $fields['first_name'] ?? '' }} {{ $fields['last_name'] ?? '' }}
                        </p>
                        <div class="pt-1 border-t dark:border-neutral-700">
                            @include('permission-registry::livewire.partials.import-row-action', ['row' => $row, 'rowActions' => $rowActions])
                        </div>
                    </div>
                @endforeach
            </div>

            @if(method_exists($stagingRows, 'hasPages') && $stagingRows->hasPages())
                <div class="p-4 border-t dark:border-neutral-700">
                    {{ $stagingRows->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- Step: Staging --}}
    @if($step === 'staging')
        <div class="bg-white dark:bg-neutral-800 rounded-lg shadow">
            <div class="p-4 border-b dark:border-neutral-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('permission-registry::messages.import.staging_title') }}
                </h3>
            </div>

            @include('permission-registry::livewire.partials.import-status-filter')

            {{-- Selection buttons --}}
            <div class="p-4 border-b dark:border-neutral-700 flex flex-wrap gap-2 items-center">
                <button wire:click="selectAll"
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors
                               bg-indigo-100 text-indigo-700 hover:bg-indigo-200
                               dark:bg-indigo-900/30 dark:text-indigo-300 dark:hover:bg-indigo-900/50">
                    {{ __('permission-registry::messages.import.select_all_total', ['count' => $stagingStats['total']]) }}
                </button>
                <button wire:click="deselectAll"
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors
                               bg-gray-100 text-gray-700 hover:bg-gray-200
                               dark:bg-neutral-700 dark:text-gray-300 dark:hover:bg-neutral-600">
                    {{ __('permission-registry::messages.import.deselect_all') }}
                </button>

                <span class="ml-auto text-sm text-gray-600 dark:text-gray-400 self-center">
                    {{ __('permission-registry::messages.import.selected_of_total', ['selected' => count($selectedRows), 'total' => $stagingStats['total']]) }}
                </span>
            </div>

            {{-- Desktop table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                    <thead class="bg-gray-50 dark:bg-neutral-700">
                        <tr>
                            @php
                                $pageIds = collect($stagingRows->items())->pluck('id')->map(fn ($id) => (int) $id)->all();
                                $pageSelectedCount = count(array_intersect($selectedRows, $pageIds));
                                $allPageSelected = $pageSelectedCount === count($pageIds) && count($pageIds) > 0;
                                $somePageSelected = $pageSelectedCount > 0 && !$allPageSelected;
                            @endphp
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-10">
                                <input type="checkbox"
                                       wire:click="{{ $allPageSelected ? 'deselectAllOnPage' : 'selectAllOnPage' }}"
                                       {{ $allPageSelected ? 'checked' : '' }}
                                       @if($somePageSelected) x-ref="pageCheckbox" x-init="$refs.pageCheckbox && ($refs.pageCheckbox.indeterminate = true)" @endif
                                       class="rounded border-gray-300 dark:border-gray-600 dark:bg-neutral-700 text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::messages.import.first_name') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::messages.import.last_name') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::messages.status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::messages.import.action_column') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                        @foreach($stagingRows as $row)
                            @php $fields = is_array($row->fields) ? $row->fields : []; @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700/50">
                                <td class="px-4 py-3">
                                    <input type="checkbox"
                                           wire:click="toggleRow({{ $row->id }})"
                                           {{ in_array($row->id, $selectedRows) ? 'checked' : '' }}
                                           class="rounded border-gray-300 dark:border-gray-600 dark:bg-neutral-700 text-indigo-600 focus:ring-indigo-500">
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $fields['email'] ?? ($row->matchedVirtualUser->name ?? '—') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $fields['first_name'] ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $fields['last_name'] ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @include('permission-registry::livewire.partials.import-status-badge', ['row' => $row])
                                </td>
                                <td class="px-4 py-3">
                                    @include('permission-registry::livewire.partials.import-row-action', ['row' => $row, 'rowActions' => $rowActions])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <div class="md:hidden divide-y divide-gray-200 dark:divide-neutral-700">
                @foreach($stagingRows as $row)
                    @php $fields = is_array($row->fields) ? $row->fields : []; @endphp
                    <div class="p-4 space-y-2">
                        <div class="flex items-start gap-3">
                            <input type="checkbox"
                                   wire:click="toggleRow({{ $row->id }})"
                                   {{ in_array($row->id, $selectedRows) ? 'checked' : '' }}
                                   class="mt-1 rounded border-gray-300 dark:border-gray-600 dark:bg-neutral-700 text-indigo-600 focus:ring-indigo-500">
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                        {{ $fields['email'] ?? $row->external_id }}
                                    </p>
                                    @include('permission-registry::livewire.partials.import-status-badge', ['row' => $row])
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $fields['first_name'] ?? '' }} {{ $fields['last_name'] ?? '' }}
                                </p>
                                <div class="pt-1 mt-1 border-t dark:border-neutral-700">
                                    @include('permission-registry::livewire.partials.import-row-action', ['row' => $row, 'rowActions' => $rowActions])
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if(method_exists($stagingRows, 'hasPages') && $stagingRows->hasPages())
                <div class="p-4 border-t dark:border-neutral-700">
                    {{ $stagingRows->links() }}
                </div>
            @endif

            {{-- Action buttons --}}
            <div class="p-4 border-t dark:border-neutral-700 flex flex-col sm:flex-row gap-2 sm:justify-end">
                <button wire:click="cancelImport"
                        class="w-full sm:w-auto px-4 py-2 text-sm font-medium rounded-md transition-colors
                               bg-white text-gray-700 border border-gray-300 hover:bg-gray-50
                               dark:bg-neutral-700 dark:text-gray-200 dark:border-neutral-600 dark:hover:bg-neutral-600">
                    {{ __('permission-registry::messages.import.cancel') }}
                </button>
                <button wire:click="approveAndExecute"
                        wire:loading.attr="disabled"
                        {{ empty($selectedRows) ? 'disabled' : '' }}
                        class="w-full sm:w-auto px-4 py-2 text-sm font-medium rounded-md transition-colors
                               bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed
                               dark:bg-indigo-500 dark:hover:bg-indigo-600">
                    <svg wire:loading wire:target="approveAndExecute" class="animate-spin -ml-1 mr-1.5 h-4 w-4 inline" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    {{ __('permission-registry::messages.import.approve_execute') }}
                </button>
            </div>
        </div>
    @endif

    {{-- Step: Executing --}}
    @if($step === 'executing')
        <div class="bg-white dark:bg-neutral-800 rounded-lg shadow p-8">
            <div class="flex flex-col items-center justify-center space-y-4">
                <svg class="animate-spin h-12 w-12 text-indigo-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <p class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ __('permission-registry::messages.import.executing') }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('permission-registry::messages.import.please_wait') }}
                </p>
            </div>
        </div>
    @endif

    {{-- Step: Done --}}
    @if($step === 'done')
        <div class="bg-white dark:bg-neutral-800 rounded-lg shadow">
            <div class="p-4 border-b dark:border-neutral-700">
                <h3 class="text-lg font-semibold text-green-700 dark:text-green-300">
                    {{ __('permission-registry::messages.import.done_title') }}
                </h3>
            </div>

            <div class="p-6 grid grid-cols-2 sm:grid-cols-5 gap-4">
                <div class="text-center p-3 rounded bg-green-50 dark:bg-green-900/20">
                    <p class="text-3xl font-bold text-green-700 dark:text-green-300">{{ $executionResult['created'] ?? 0 }}</p>
                    <p class="text-xs text-green-600 dark:text-green-400 mt-1">{{ __('permission-registry::messages.import.result_created') }}</p>
                </div>
                <div class="text-center p-3 rounded bg-blue-50 dark:bg-blue-900/20">
                    <p class="text-3xl font-bold text-blue-700 dark:text-blue-300">{{ $executionResult['updated'] ?? 0 }}</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">{{ __('permission-registry::messages.import.result_updated') }}</p>
                </div>
                <div class="text-center p-3 rounded bg-red-50 dark:bg-red-900/20">
                    <p class="text-3xl font-bold text-red-700 dark:text-red-300">{{ $executionResult['fired'] ?? 0 }}</p>
                    <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ __('permission-registry::messages.import.result_fired') }}</p>
                </div>
                <div class="text-center p-3 rounded bg-gray-50 dark:bg-neutral-700">
                    <p class="text-3xl font-bold text-gray-500 dark:text-gray-400">{{ $executionResult['skipped'] ?? 0 }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('permission-registry::messages.import.result_skipped') }}</p>
                </div>
                <div class="text-center p-3 rounded bg-orange-50 dark:bg-orange-900/20">
                    <p class="text-3xl font-bold text-orange-700 dark:text-orange-300">{{ $executionResult['errors'] ?? 0 }}</p>
                    <p class="text-xs text-orange-600 dark:text-orange-400 mt-1">{{ __('permission-registry::messages.import.result_errors') }}</p>
                </div>
            </div>

            <div class="p-4 border-t dark:border-neutral-700 flex justify-end">
                <button wire:click="backToList"
                        class="px-4 py-2 text-sm font-medium rounded-md transition-colors
                               bg-gray-100 text-gray-700 hover:bg-gray-200
                               dark:bg-neutral-700 dark:text-gray-300 dark:hover:bg-neutral-600">
                    {{ __('permission-registry::messages.back') }}
                </button>
            </div>
        </div>
    @endif
</div>
