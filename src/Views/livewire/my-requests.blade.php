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
                {{ __('permission-registry::messages.my_requests') }}
            </h3>
            <select wire:model.live="statusFilter"
                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200 shadow-sm text-sm sm:w-48">
                <option value="">{{ __('permission-registry::messages.all_statuses') }}</option>
                @foreach(\ArcheeNic\PermissionRegistry\Enums\ApprovalRequestStatus::cases() as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>

        @if($this->requests->isEmpty())
            <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                {{ __('permission-registry::messages.no_requests') }}
            </div>
        @else
            {{-- Desktop table --}}
            <div class="hidden sm:block">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-neutral-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::messages.permission_name') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::messages.requested_at') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::messages.status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::messages.decisions') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->requests as $req)
                            <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700/50">
                                <td class="px-4 py-3 text-sm">
                                    <p class="text-gray-900 dark:text-gray-100 font-medium">
                                        {{ $req->grantedPermission?->permission?->name ?? '—' }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $req->grantedPermission?->permission?->service ?? '' }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $req->created_at?->format('d.m.Y H:i') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <x-pr::approval-status-badge :status="$req->status->value" />
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($req->decisions->isNotEmpty())
                                        <div class="space-y-1">
                                            @foreach($req->decisions as $decision)
                                                <div class="flex items-center gap-1 text-xs">
                                                    <x-pr::approval-status-badge :status="$decision->decision->value" />
                                                    @if($decision->comment)
                                                        <span class="text-gray-400 dark:text-gray-500 italic truncate max-w-[150px]">{{ $decision->comment }}</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($req->status->value === 'pending')
                                        <button wire:click="cancelRequest({{ $req->id }})"
                                                wire:confirm="{{ __('permission-registry::messages.confirm_cancel') }}"
                                                class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-sm transition">
                                            {{ __('permission-registry::messages.cancel') }}
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <div class="sm:hidden divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($this->requests as $req)
                    <div class="p-4">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $req->grantedPermission?->permission?->name ?? '—' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $req->grantedPermission?->permission?->service ?? '' }}
                                </p>
                            </div>
                            <x-pr::approval-status-badge :status="$req->status->value" />
                        </div>

                        <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">
                            {{ $req->created_at?->format('d.m.Y H:i') ?? '—' }}
                        </p>

                        @if($req->decisions->isNotEmpty())
                            <div class="space-y-1 mb-2">
                                @foreach($req->decisions as $decision)
                                    <div class="flex items-center gap-1 text-xs">
                                        <x-pr::approval-status-badge :status="$decision->decision->value" />
                                        @if($decision->comment)
                                            <span class="text-gray-400 dark:text-gray-500 italic">{{ $decision->comment }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if($req->status->value === 'pending')
                            <button wire:click="cancelRequest({{ $req->id }})"
                                    wire:confirm="{{ __('permission-registry::messages.confirm_cancel') }}"
                                    class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-sm transition">
                                {{ __('permission-registry::messages.cancel') }}
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
