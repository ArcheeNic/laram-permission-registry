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
                                    <td class="px-4 py-3 text-right">
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
                            <div class="flex justify-end">
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
                                <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700/50">
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
                        <div class="p-4 space-y-2">
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

    {{-- Step: Staging --}}
    @if($step === 'staging')
        <div class="bg-white dark:bg-neutral-800 rounded-lg shadow">
            <div class="p-4 border-b dark:border-neutral-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('permission-registry::messages.import.staging_title') }}
                </h3>
            </div>

            {{-- Stats --}}
            <div class="p-4 border-b dark:border-neutral-700 grid grid-cols-2 sm:grid-cols-5 gap-3">
                <div class="text-center p-2 rounded bg-gray-50 dark:bg-neutral-700">
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stagingStats['total'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('permission-registry::messages.import.total') }}</p>
                </div>
                <div class="text-center p-2 rounded bg-green-50 dark:bg-green-900/20">
                    <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $stagingStats['new'] }}</p>
                    <p class="text-xs text-green-600 dark:text-green-400">{{ __('permission-registry::messages.import.status_new') }}</p>
                </div>
                <div class="text-center p-2 rounded bg-yellow-50 dark:bg-yellow-900/20">
                    <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">{{ $stagingStats['changed'] }}</p>
                    <p class="text-xs text-yellow-600 dark:text-yellow-400">{{ __('permission-registry::messages.import.status_changed') }}</p>
                </div>
                <div class="text-center p-2 rounded bg-gray-50 dark:bg-neutral-700">
                    <p class="text-2xl font-bold text-gray-500 dark:text-gray-400">{{ $stagingStats['exists'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('permission-registry::messages.import.status_exists') }}</p>
                </div>
                <div class="text-center p-2 rounded bg-red-50 dark:bg-red-900/20">
                    <p class="text-2xl font-bold text-red-700 dark:text-red-300">{{ $stagingStats['missing'] }}</p>
                    <p class="text-xs text-red-600 dark:text-red-400">{{ __('permission-registry::messages.import.status_missing') }}</p>
                </div>
            </div>

            {{-- Selection buttons --}}
            <div class="p-4 border-b dark:border-neutral-700 flex flex-wrap gap-2">
                <button wire:click="selectAll"
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors
                               bg-gray-100 text-gray-700 hover:bg-gray-200
                               dark:bg-neutral-700 dark:text-gray-300 dark:hover:bg-neutral-600">
                    {{ __('permission-registry::messages.import.select_all') }}
                </button>
                <button wire:click="selectByStatus('new')"
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors
                               bg-green-100 text-green-700 hover:bg-green-200
                               dark:bg-green-900/30 dark:text-green-300 dark:hover:bg-green-900/50">
                    {{ __('permission-registry::messages.import.select_new') }}
                </button>
                <button wire:click="selectByStatus('missing')"
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors
                               bg-red-100 text-red-700 hover:bg-red-200
                               dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50">
                    {{ __('permission-registry::messages.import.select_missing') }}
                </button>
                <button wire:click="deselectAll"
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors
                               bg-gray-100 text-gray-700 hover:bg-gray-200
                               dark:bg-neutral-700 dark:text-gray-300 dark:hover:bg-neutral-600">
                    {{ __('permission-registry::messages.import.deselect_all') }}
                </button>

                <span class="ml-auto text-sm text-gray-600 dark:text-gray-400 self-center">
                    {{ __('permission-registry::messages.bulk_selected_count', ['count' => count($selectedRows)]) }}
                </span>
            </div>

            {{-- Desktop table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                    <thead class="bg-gray-50 dark:bg-neutral-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-10">
                                <input type="checkbox"
                                       wire:click="{{ count($selectedRows) === $stagingStats['total'] ? 'deselectAll' : 'selectAll' }}"
                                       {{ count($selectedRows) === $stagingStats['total'] && $stagingStats['total'] > 0 ? 'checked' : '' }}
                                       class="rounded border-gray-300 dark:border-gray-600 dark:bg-neutral-700 text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                External ID
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Email
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                {{ __('permission-registry::messages.import.first_name') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                {{ __('permission-registry::messages.import.last_name') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                {{ __('permission-registry::messages.status') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                        @foreach($stagingRows as $row)
                            @php
                                $fields = is_array($row->fields) ? $row->fields : [];
                                $matchStatus = $row->match_status instanceof \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus
                                    ? $row->match_status
                                    : \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::tryFrom($row->match_status);
                                $badgeClasses = match($matchStatus) {
                                    \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::NEW => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                    \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::CHANGED => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                                    \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::EXISTS => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                    \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::MISSING => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                    default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                };
                                $statusLabel = match($matchStatus) {
                                    \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::NEW => __('permission-registry::messages.import.status_new'),
                                    \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::CHANGED => __('permission-registry::messages.import.status_changed'),
                                    \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::EXISTS => __('permission-registry::messages.import.status_exists'),
                                    \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::MISSING => __('permission-registry::messages.import.status_missing'),
                                    default => '—',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700/50">
                                <td class="px-4 py-3">
                                    <input type="checkbox"
                                           wire:click="toggleRow({{ $row->id }})"
                                           {{ in_array($row->id, $selectedRows) ? 'checked' : '' }}
                                           class="rounded border-gray-300 dark:border-gray-600 dark:bg-neutral-700 text-indigo-600 focus:ring-indigo-500">
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $row->external_id }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $fields['email'] ?? ($row->matchedVirtualUser->name ?? '—') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $fields['first_name'] ?? $fields['firstname'] ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $fields['last_name'] ?? $fields['lastname'] ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeClasses }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <div class="md:hidden divide-y divide-gray-200 dark:divide-neutral-700">
                @foreach($stagingRows as $row)
                    @php
                        $fields = is_array($row->fields) ? $row->fields : [];
                        $matchStatus = $row->match_status instanceof \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus
                            ? $row->match_status
                            : \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::tryFrom($row->match_status);
                        $badgeClasses = match($matchStatus) {
                            \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::NEW => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                            \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::CHANGED => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                            \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::EXISTS => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                            \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::MISSING => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                            default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                        };
                        $statusLabel = match($matchStatus) {
                            \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::NEW => __('permission-registry::messages.import.status_new'),
                            \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::CHANGED => __('permission-registry::messages.import.status_changed'),
                            \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::EXISTS => __('permission-registry::messages.import.status_exists'),
                            \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::MISSING => __('permission-registry::messages.import.status_missing'),
                            default => '—',
                        };
                    @endphp
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
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeClasses }}">
                                        {{ $statusLabel }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $fields['first_name'] ?? $fields['firstname'] ?? '' }}
                                    {{ $fields['last_name'] ?? $fields['lastname'] ?? '' }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

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
