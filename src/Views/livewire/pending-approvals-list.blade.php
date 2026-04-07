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

    @if($selectedRequestId && $this->selectedRequest)
        {{-- Детальный вид запроса --}}
        @php $req = $this->selectedRequest; @endphp
        <div class="bg-white dark:bg-neutral-800 rounded-lg shadow p-6">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('permission-registry::Approval Policy') }} #{{ $req->id }}
                </h3>
                <button wire:click="closeDetail" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-xl">&times;</button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('permission-registry::User') }}</span>
                    <p class="text-gray-900 dark:text-gray-100 font-medium">{{ $req->grantedPermission->user->name ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('permission-registry::Permission') }}</span>
                    <p class="text-gray-900 dark:text-gray-100 font-medium">
                        {{ $req->grantedPermission->permission->service }} / {{ $req->grantedPermission->permission->name }}
                    </p>
                </div>
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('permission-registry::Requested at') }}</span>
                    <p class="text-gray-900 dark:text-gray-100">{{ $req->created_at->format('d.m.Y H:i') }}</p>
                </div>
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('permission-registry::Status') }}</span>
                    <x-pr::approval-status-badge :status="$req->status->value" />
                </div>
            </div>

            {{-- Решения --}}
            @if($req->decisions->isNotEmpty())
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('permission-registry::Decisions') }}</h4>
                    <div class="space-y-2">
                        @foreach($req->decisions as $decision)
                            <div class="flex items-center gap-3 bg-gray-50 dark:bg-neutral-700 rounded px-3 py-2 text-sm">
                                <x-pr::approval-status-badge :status="$decision->decision->value" />
                                <span class="text-gray-800 dark:text-gray-200">{{ $decision->approver->name ?? '#' . $decision->approver_id }}</span>
                                @if($decision->comment)
                                    <span class="text-gray-500 dark:text-gray-400 italic">{{ $decision->comment }}</span>
                                @endif
                                <span class="text-gray-400 dark:text-gray-500 ml-auto">{{ $decision->decided_at->format('d.m.Y H:i') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Форма решения --}}
            @if($req->status->value === 'pending')
                <div class="border-t dark:border-gray-700 pt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('permission-registry::Comment') }}
                        <x-perm::field-hint
                            :title="__('permission-registry::hints.pending_approvals_comment_title')"
                            :description="__('permission-registry::hints.pending_approvals_comment_desc')"
                        />
                    </label>
                    <textarea wire:model="comment" rows="2"
                              class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200 shadow-sm text-sm mb-3"></textarea>

                    <div class="flex gap-2">
                        <button wire:click="approve"
                                class="px-4 py-2 bg-green-600 text-white text-sm rounded hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 transition">
                            {{ __('permission-registry::Approve') }}
                        </button>
                        <button wire:click="reject"
                                class="px-4 py-2 bg-red-600 text-white text-sm rounded hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 transition">
                            {{ __('permission-registry::Reject') }}
                        </button>
                    </div>
                </div>
            @endif
        </div>
    @else
        {{-- Список --}}
        <div class="bg-white dark:bg-neutral-800 rounded-lg shadow">
            <div class="p-4 border-b dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('permission-registry::Pending Approvals') }}
                    @if($this->pendingApprovals->isNotEmpty())
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                            {{ $this->pendingApprovals->count() }}
                        </span>
                    @endif
                </h3>
            </div>

            @if($this->pendingApprovals->isEmpty())
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    {{ __('permission-registry::No pending approvals') }}
                </div>
            @else
                {{-- Desktop table --}}
                <div class="hidden sm:block">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-neutral-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::User') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::Permission') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::Requested at') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('permission-registry::Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->pendingApprovals as $request)
                                <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700/50 cursor-pointer" wire:click="selectRequest({{ $request->id }})">
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $request->grantedPermission->user->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $request->grantedPermission->permission->service }} / {{ $request->grantedPermission->permission->name }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $request->created_at->format('d.m.Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-indigo-600 dark:text-indigo-400 text-sm">{{ __('permission-registry::View') }} →</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile cards --}}
                <div class="sm:hidden divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($this->pendingApprovals as $request)
                        <div wire:click="selectRequest({{ $request->id }})"
                             class="p-4 hover:bg-gray-50 dark:hover:bg-neutral-700/50 cursor-pointer">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $request->grantedPermission->user->name ?? '—' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $request->grantedPermission->permission->service }} / {{ $request->grantedPermission->permission->name }}
                                    </p>
                                </div>
                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $request->created_at->format('d.m.Y') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
