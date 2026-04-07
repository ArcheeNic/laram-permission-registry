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

    <div class="bg-white dark:bg-neutral-800 rounded-lg shadow">
        <div class="p-4 border-b dark:border-neutral-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ __('permission-registry::messages.my_permissions') }}
            </h3>
            <button wire:click="openRequestModal"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 transition">
                {{ __('permission-registry::messages.request_permission') }}
            </button>
        </div>

        {{-- Фильтры --}}
        <div class="p-4 border-b dark:border-neutral-700 flex flex-col sm:flex-row gap-3">
            <input wire:model.live.debounce.300ms="search" type="text"
                   placeholder="{{ __('permission-registry::messages.search_placeholder') }}"
                   class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200 shadow-sm text-sm" />
            <select wire:model.live="statusFilter"
                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200 shadow-sm text-sm sm:w-48">
                <option value="">{{ __('permission-registry::messages.all_statuses') }}</option>
                @foreach(\ArcheeNic\PermissionRegistry\Enums\GrantedPermissionStatus::cases() as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>

        @if($this->permissions->isEmpty())
            <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                {{ __('permission-registry::messages.no_permissions') }}
            </div>
        @else
            {{-- Desktop table --}}
            <div class="hidden sm:block">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-neutral-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::messages.service') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::messages.permission_name') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::messages.status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::messages.granted_at') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->permissions as $gp)
                            <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700/50">
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $gp->permission->service ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $gp->permission->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @include('permission-registry::livewire.partials.my-permission-status-badge', ['status' => $gp->status])
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $gp->granted_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <div class="sm:hidden divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($this->permissions as $gp)
                    <div class="p-4">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $gp->permission->name ?? '—' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $gp->permission->service ?? '—' }}</p>
                            </div>
                            @include('permission-registry::livewire.partials.my-permission-status-badge', ['status' => $gp->status])
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $gp->granted_at?->format('d.m.Y H:i') ?? '—' }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Модалка запроса права --}}
    @if($showRequestModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50 transition-opacity" wire:click="closeRequestModal"></div>

                <div class="relative bg-white dark:bg-neutral-800 rounded-lg shadow-xl w-full max-w-lg p-6 z-10">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ __('permission-registry::messages.request_permission') }}
                        </h3>
                        <button wire:click="closeRequestModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-xl">&times;</button>
                    </div>

                    @if(!$selectedPermissionId)
                        {{-- Каталог доступных прав --}}
                        @if($this->availablePermissions->isEmpty())
                            <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
                                {{ __('permission-registry::messages.no_available_permissions') }}
                            </p>
                        @else
                            <div class="max-h-80 overflow-y-auto divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($this->availablePermissions as $perm)
                                    <button wire:click="selectPermission({{ $perm->id }})"
                                            class="w-full text-left px-3 py-3 hover:bg-gray-50 dark:hover:bg-neutral-700 transition">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $perm->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $perm->service }}</p>
                                        @if($perm->description)
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $perm->description }}</p>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    @else
                        {{-- Форма запроса --}}
                        @php $selectedPerm = \ArcheeNic\PermissionRegistry\Models\Permission::with('fields')->find($selectedPermissionId); @endphp
                        @if($selectedPerm)
                            <div class="mb-4">
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    <span class="font-medium">{{ $selectedPerm->service }}</span> / {{ $selectedPerm->name }}
                                </p>
                            </div>

                            @if(collect($fieldValues)->isNotEmpty())
                                <div class="space-y-3 mb-4">
                                    @foreach($selectedPerm->fields->where('is_global', false) as $field)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                {{ $field->name }}
                                                @if($field->is_required) <span class="text-red-500">*</span> @endif
                                            </label>
                                            <input type="text"
                                                   wire:model="fieldValues.{{ $field->id }}"
                                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200 shadow-sm text-sm"
                                                   placeholder="{{ $field->description ?? '' }}" />
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="flex gap-2 justify-end">
                                <button wire:click="$set('selectedPermissionId', null)"
                                        class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-neutral-700 rounded-md hover:bg-gray-200 dark:hover:bg-neutral-600 transition">
                                    {{ __('permission-registry::messages.back') }}
                                </button>
                                <button wire:click="requestPermission"
                                        class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-md hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 transition">
                                    {{ __('permission-registry::messages.submit_request') }}
                                </button>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
