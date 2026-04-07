<div class="bg-white dark:bg-neutral-800 rounded-lg shadow p-4 mt-4">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
        {{ __('permission-registry::Approval Policy') }}
    </h3>

    @if($flashMessage)
        <div class="mb-3 p-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded text-sm">
            {{ $flashMessage }}
        </div>
    @endif

    @if($flashError)
        <div class="mb-3 p-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded text-sm">
            {{ $flashError }}
        </div>
    @endif

    @if(!$hasPolicy)
        <p class="text-gray-500 dark:text-gray-400 text-sm mb-3">{{ __('permission-registry::No approval policy') }}</p>
        <button wire:click="enablePolicy"
                class="px-3 py-1.5 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 transition">
            {{ __('permission-registry::Enable Approval') }}
        </button>
    @elseif(!$isActive)
        <p class="text-gray-500 dark:text-gray-400 text-sm mb-3">{{ __('permission-registry::Approval disabled') }}</p>
        <button wire:click="reEnablePolicy"
                class="px-3 py-1.5 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 transition">
            {{ __('permission-registry::Enable Approval') }}
        </button>
    @else
        <div class="space-y-4">
            {{-- Тип подтверждения --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('permission-registry::Approval Type') }}
                        <x-perm::field-hint
                            :title="__('permission-registry::hints.approval_policy_approval_type_title')"
                            :description="__('permission-registry::hints.approval_policy_approval_type_desc')"
                        />
                    </label>
                    <select wire:model.live="approvalType"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200 shadow-sm text-sm">
                        @foreach($this->approvalTypes as $type)
                            <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                @if($approvalType === 'n_of_m')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('permission-registry::Required Count') }}
                            <x-perm::field-hint
                                :title="__('permission-registry::hints.approval_policy_required_count_title')"
                                :description="__('permission-registry::hints.approval_policy_required_count_desc')"
                            />
                        </label>
                        <input type="number" wire:model.live="requiredCount" min="1"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200 shadow-sm text-sm">
                    </div>
                @endif

                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" wire:model.live="isActive"
                               class="rounded border-gray-300 dark:border-gray-600 text-indigo-600">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('permission-registry::Policy is active') }}</span>
                        <x-perm::field-hint
                            :title="__('permission-registry::hints.approval_policy_is_active_title')"
                            :description="__('permission-registry::hints.approval_policy_is_active_desc')"
                        />
                    </label>
                </div>
            </div>

            {{-- Список подтверждающих --}}
            <div>
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('permission-registry::Approvers') }}</h4>

                @if(count($approvers) > 0)
                    <div class="space-y-2 mb-3">
                        @foreach($approvers as $approver)
                            <div class="flex items-center justify-between bg-gray-50 dark:bg-neutral-700 rounded px-3 py-2">
                                <div class="text-sm text-gray-800 dark:text-gray-200">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 mr-2">
                                        {{ __("permission-registry::approvals.approver_type.{$approver['type']}") }}
                                    </span>
                                    {{ $approver['label'] }}
                                </div>
                                <button wire:click="removeApprover({{ $approver['id'] }})"
                                        class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-sm">
                                    &times;
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Добавление подтверждающего --}}
                <div class="flex flex-col sm:flex-row gap-2">
                    <select wire:model.live="newApproverType"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200 shadow-sm text-sm">
                        <option value="virtual_user">{{ __('permission-registry::approvals.approver_type.virtual_user') }}</option>
                        <option value="position">{{ __('permission-registry::approvals.approver_type.position') }}</option>
                    </select>

                    @if($newApproverType === 'virtual_user')
                        <select wire:model="newApproverId"
                                class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200 shadow-sm text-sm">
                            <option value="">{{ __('permission-registry::Select user') }}</option>
                            @foreach($this->availableUsers as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <select wire:model="newApproverId"
                                class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200 shadow-sm text-sm">
                            <option value="">{{ __('permission-registry::Select position') }}</option>
                            @foreach($this->availablePositions as $position)
                                <option value="{{ $position->id }}">{{ $position->name }}</option>
                            @endforeach
                        </select>
                    @endif

                    <button wire:click="addApprover"
                            class="px-3 py-1.5 bg-green-600 text-white text-sm rounded hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 transition whitespace-nowrap">
                        {{ __('permission-registry::Add Approver') }}
                    </button>
                </div>
            </div>

            {{-- Кнопки --}}
            <div class="flex gap-2 pt-2 border-t dark:border-gray-700">
                <button wire:click="savePolicy"
                        class="px-3 py-1.5 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 transition">
                    {{ __('permission-registry::Save') }}
                </button>
                @if($isActive)
                    <button wire:click="removePolicy"
                            wire:confirm="{{ __('permission-registry::Are you sure?') }}"
                            class="px-3 py-1.5 bg-red-600 text-white text-sm rounded hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 transition">
                        {{ __('permission-registry::Disable Approval') }}
                    </button>
                @else
                    <button wire:click="reEnablePolicy"
                            class="px-3 py-1.5 bg-green-600 text-white text-sm rounded hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 transition">
                        {{ __('permission-registry::Enable Approval') }}
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>
