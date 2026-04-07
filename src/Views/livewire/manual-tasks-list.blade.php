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

    {{-- Filters --}}
    <div class="mb-4 flex flex-col sm:flex-row justify-between gap-4">
        <select wire:model.live="statusFilter"
                class="rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200
                       focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            <option value="">{{ __('permission-registry::messages.status') }}: {{ __('permission-registry::governance.all_statuses') }}</option>
            @foreach($statuses as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>

        <div class="flex items-center gap-2">
            <span class="text-gray-700 dark:text-gray-300">{{ __('permission-registry::governance.show') }}:</span>
            <select wire:model.live="perPage"
                    class="rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200
                           focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>

    <div class="bg-white dark:bg-neutral-800 rounded-lg shadow">
        <div class="p-4 border-b dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ __('permission-registry::governance.manual_tasks') }}
                @if($tasks->total() > 0)
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                        {{ $tasks->total() }}
                    </span>
                @endif
            </h3>
        </div>

        @if($tasks->isEmpty())
            <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                {{ __('permission-registry::governance.no_manual_tasks') }}
            </div>
        @else
            {{-- Desktop table --}}
            <div class="hidden md:block">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-neutral-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::messages.permission') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::governance.user') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::governance.assigned_to') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::messages.status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase hidden lg:table-cell">{{ __('permission-registry::governance.due_at') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($tasks as $task)
                            <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700/50">
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $task->grantedPermission->permission->service ?? '—' }} / {{ $task->grantedPermission->permission->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $task->grantedPermission->user->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $task->assignee->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @php
                                        $taskStatusClasses = match($task->status->value) {
                                            'pending' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                            'in_progress' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                                            'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                            'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                            'expired' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
                                            default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $taskStatusClasses }}">
                                        {{ $task->status->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 hidden lg:table-cell">
                                    {{ $task->due_at?->format('d.m.Y H:i') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if(!$task->status->isTerminal())
                                        <button wire:click="selectTask({{ $task->id }})"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md transition-colors
                                                       bg-green-100 text-green-700 hover:bg-green-200
                                                       dark:bg-green-900 dark:text-green-300 dark:hover:bg-green-800">
                                            {{ __('permission-registry::governance.complete_task') }}
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($tasks as $task)
                    <div class="p-4 space-y-2">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $task->grantedPermission->permission->service ?? '—' }} / {{ $task->grantedPermission->permission->name ?? '—' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $task->grantedPermission->user->name ?? '—' }}
                                </p>
                            </div>
                            @php
                                $taskStatusClasses = match($task->status->value) {
                                    'pending' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                    'in_progress' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                                    'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                    'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                    'expired' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
                                    default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $taskStatusClasses }}">
                                {{ $task->status->label() }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ __('permission-registry::governance.assigned_to') }}: {{ $task->assignee->name ?? '—' }}</span>
                            <span>{{ $task->due_at?->format('d.m.Y') ?? '' }}</span>
                        </div>
                        @if(!$task->status->isTerminal())
                            <div class="flex justify-end">
                                <button wire:click="selectTask({{ $task->id }})"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md transition-colors
                                               bg-green-100 text-green-700 hover:bg-green-200
                                               dark:bg-green-900 dark:text-green-300 dark:hover:bg-green-800">
                                    {{ __('permission-registry::governance.complete_task') }}
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="p-4 border-t dark:border-gray-700">
            {{ $tasks->links() }}
        </div>
    </div>

    {{-- Complete Task Modal --}}
    @if($selectedTaskId)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity"
                     wire:click="closeModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-neutral-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="px-4 pt-5 pb-4 sm:p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            {{ __('permission-registry::governance.complete_task') }}
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ __('permission-registry::governance.evidence_type_label') }}
                                    <x-perm::field-hint
                                        :title="__('permission-registry::hints.manual_tasks_evidence_type_title')"
                                        :description="__('permission-registry::hints.manual_tasks_evidence_type_desc')"
                                    />
                                </label>
                                <select wire:model="evidenceType"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200 shadow-sm text-sm">
                                    @foreach($evidenceTypes as $type)
                                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ __('permission-registry::governance.evidence_value') }}
                                    <x-perm::field-hint
                                        :title="__('permission-registry::hints.manual_tasks_evidence_value_title')"
                                        :description="__('permission-registry::hints.manual_tasks_evidence_value_desc')"
                                    />
                                </label>
                                <textarea wire:model="evidenceValue" rows="3"
                                          class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200 shadow-sm text-sm"
                                          placeholder="{{ __('permission-registry::governance.evidence_value') }}"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-neutral-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button wire:click="completeTask"
                                class="w-full sm:w-auto px-4 py-2 bg-green-600 text-white text-sm rounded hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 transition">
                            {{ __('permission-registry::governance.complete_task') }}
                        </button>
                        <button wire:click="closeModal"
                                class="mt-3 sm:mt-0 w-full sm:w-auto px-4 py-2 bg-white dark:bg-neutral-600 text-gray-700 dark:text-gray-200 text-sm rounded border border-gray-300 dark:border-gray-500 hover:bg-gray-50 dark:hover:bg-neutral-500 transition">
                            {{ __('permission-registry::messages.cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
