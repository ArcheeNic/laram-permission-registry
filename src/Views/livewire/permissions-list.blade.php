<div>
    @if (session('success'))
        <div class="mb-4 rounded-md border-l-4 border-green-500 bg-green-100 p-4 text-green-700 dark:border-green-400 dark:bg-green-900/30 dark:text-green-300" role="alert">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-md border-l-4 border-red-500 bg-red-100 p-4 text-red-700 dark:border-red-400 dark:bg-red-900/30 dark:text-red-300" role="alert">
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <div class="mb-4 flex flex-col gap-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <input wire:model.live="search" type="text" placeholder="{{ __('permission-registry::Search') }}"
                   class="rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200
                          focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">

            <select wire:model.live="service"
                    class="rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200
                           focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value="">{{ __('permission-registry::All services') }}</option>
                @foreach($services as $serviceName)
                    <option value="{{ $serviceName }}">{{ $serviceName }}</option>
                @endforeach
            </select>

            <select wire:model.live="scope"
                    class="rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200
                           focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value="">{{ __('permission-registry::All scopes') }}</option>
                @foreach($scopes as $scopeOption)
                    <option value="{{ $scopeOption->value }}">{{ $scopeOption->label() }}</option>
                @endforeach
            </select>

            <select wire:model.live="managementMode"
                    class="rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200
                           focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value="">{{ __('permission-registry::governance.management_mode_label') }}</option>
                @foreach($managementModes as $mode)
                    <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                @endforeach
            </select>

            <select wire:model.live="riskLevel"
                    class="rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200
                           focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value="">{{ __('permission-registry::governance.risk_level_label') }}</option>
                @foreach($riskLevels as $level)
                    <option value="{{ $level->value }}">{{ $level->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-2 self-end">
            <span class="text-gray-700 dark:text-gray-300">{{ __('permission-registry::Show') }}:</span>
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

    <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-sm sm:rounded-lg">
        {{-- Desktop list (cards, not table) --}}
        <div class="hidden md:block divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($permissions as $permission)
                @php
                    $scope = $permission->scope ?? null;
                    $scopeClasses = $scope?->value === 'resource'
                        ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300'
                        : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200';
                    $modeClasses = match($permission->management_mode?->value) {
                        'automated' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                        'manual' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                        'declarative' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
                        default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                    };
                    $riskClasses = match($permission->risk_level?->value) {
                        'low' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                        'medium' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                        'high' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
                        'critical' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                        default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                    };
                @endphp
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-neutral-700/50 flex items-start gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-mono uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $permission->service }}</span>
                            <span class="text-base font-medium text-gray-900 dark:text-gray-100 truncate">
                                {{ $permission->display_name ?? $permission->name }}
                            </span>
                            @if($permission->display_name)
                                <span class="text-xs font-mono text-gray-400 dark:text-gray-500 truncate">{{ $permission->name }}</span>
                            @endif
                        </div>

                        <div class="mt-1 flex flex-wrap items-center gap-1.5 text-xs">
                            @if($scope)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium {{ $scopeClasses }}">
                                    {{ $scope->label() }}@if($permission->resource_kind) · {{ $permission->resource_kind }}@endif
                                </span>
                            @endif
                            @if($permission->management_mode)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium {{ $modeClasses }}">
                                    {{ $permission->management_mode->label() }}
                                </span>
                            @endif
                            @if($permission->risk_level)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium {{ $riskClasses }}">
                                    {{ $permission->risk_level->label() }}
                                </span>
                            @endif
                            @if($permission->auto_grant)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300"
                                      title="{{ __('permission-registry::Auto grant enabled') }}">↑ auto-grant</span>
                            @endif
                            @if($permission->auto_revoke)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300"
                                      title="{{ __('permission-registry::Auto revoke enabled') }}">↓ auto-revoke</span>
                            @endif
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                                  title="{{ __('permission-registry::Users') }}">
                                👥 {{ $permission->granted_permissions_count ?? 0 }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-1 shrink-0">
                        <a href="{{ route('permission-registry::permissions.show', $permission) }}"
                           title="{{ __('permission-registry::View') }}"
                           class="inline-flex items-center justify-center w-8 h-8 rounded-md transition-colors
                                  bg-blue-100 text-blue-700 hover:bg-blue-200
                                  dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z"/>
                            </svg>
                            <span class="sr-only">{{ __('permission-registry::View') }}</span>
                        </a>
                        <a href="{{ route('permission-registry::permissions.edit', $permission) }}"
                           title="{{ __('permission-registry::Edit') }}"
                           class="inline-flex items-center justify-center w-8 h-8 rounded-md transition-colors
                                  bg-amber-100 text-amber-700 hover:bg-amber-200
                                  dark:bg-amber-900 dark:text-amber-300 dark:hover:bg-amber-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span class="sr-only">{{ __('permission-registry::Edit') }}</span>
                        </a>
                        <form action="{{ route('permission-registry::permissions.copy', $permission) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    title="{{ __('permission-registry::Copy') }}"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-md transition-colors
                                           bg-green-100 text-green-700 hover:bg-green-200
                                           dark:bg-green-900 dark:text-green-300 dark:hover:bg-green-800">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 10h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <span class="sr-only">{{ __('permission-registry::Copy') }}</span>
                            </button>
                        </form>
                        <button wire:click="confirmDelete({{ $permission->id }})"
                                type="button"
                                title="{{ __('permission-registry::Delete') }}"
                                class="inline-flex items-center justify-center w-8 h-8 rounded-md transition-colors
                                       bg-red-100 text-red-700 hover:bg-red-200
                                       dark:bg-red-900 dark:text-red-300 dark:hover:bg-red-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-7 0h8"/>
                            </svg>
                            <span class="sr-only">{{ __('permission-registry::Delete') }}</span>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($permissions as $permission)
                <div class="p-4 space-y-2">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $permission->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $permission->service }}</p>
                        </div>
                        <div class="flex gap-1">
                            @if($permission->management_mode)
                                @php
                                    $modeClasses = match($permission->management_mode->value) {
                                        'automated' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                                        'manual' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                        'declarative' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
                                        default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $modeClasses }}">
                                    {{ $permission->management_mode->label() }}
                                </span>
                            @endif
                            @if($permission->risk_level)
                                @php
                                    $riskClasses = match($permission->risk_level->value) {
                                        'low' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                        'medium' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                                        'high' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
                                        'critical' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                        default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $riskClasses }}">
                                    {{ $permission->risk_level->label() }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <a href="{{ route('permission-registry::permissions.show', $permission) }}"
                           title="{{ __('permission-registry::View') }}"
                           class="inline-flex items-center justify-center w-8 h-8 rounded-md transition-colors
                                  bg-blue-100 text-blue-700 hover:bg-blue-200
                                  dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z"/>
                            </svg>
                            <span class="sr-only">{{ __('permission-registry::View') }}</span>
                        </a>
                        <a href="{{ route('permission-registry::permissions.edit', $permission) }}"
                           title="{{ __('permission-registry::Edit') }}"
                           class="inline-flex items-center justify-center w-8 h-8 rounded-md transition-colors
                                  bg-amber-100 text-amber-700 hover:bg-amber-200
                                  dark:bg-amber-900 dark:text-amber-300 dark:hover:bg-amber-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span class="sr-only">{{ __('permission-registry::Edit') }}</span>
                        </a>
                        <form action="{{ route('permission-registry::permissions.copy', $permission) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    title="{{ __('permission-registry::Copy') }}"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-md transition-colors
                                           bg-green-100 text-green-700 hover:bg-green-200
                                           dark:bg-green-900 dark:text-green-300 dark:hover:bg-green-800">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 10h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <span class="sr-only">{{ __('permission-registry::Copy') }}</span>
                            </button>
                        </form>
                        <button wire:click="confirmDelete({{ $permission->id }})"
                                type="button"
                                title="{{ __('permission-registry::Delete') }}"
                                class="inline-flex items-center justify-center w-8 h-8 rounded-md transition-colors
                                       bg-red-100 text-red-700 hover:bg-red-200
                                       dark:bg-red-900 dark:text-red-300 dark:hover:bg-red-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-7 0h8"/>
                            </svg>
                            <span class="sr-only">{{ __('permission-registry::Delete') }}</span>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="p-4 border-t dark:border-gray-700">
            {{ $permissions->links() }}
        </div>
    </div>

    @if($confirmingDelete)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500 dark:bg-gray-700 bg-opacity-75">
            <div class="w-full max-w-md overflow-hidden rounded-lg bg-white shadow-xl dark:bg-neutral-800">
                <div class="px-6 py-4">
                    <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ __('permission-registry::Confirm Delete Permission') }}
                    </div>
                    <div class="mt-4 text-sm text-gray-600 dark:text-gray-300">
                        {{ __('permission-registry::Are you sure you want to delete this permission?') }}
                    </div>

                    @if($deleteError)
                        <div class="mt-4 rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-700 dark:border-red-500/60 dark:bg-red-900/30 dark:text-red-300">
                            {{ $deleteError }}
                        </div>
                    @endif
                </div>
                <div class="flex justify-end space-x-3 bg-gray-100 px-6 py-4 dark:bg-neutral-700">
                    <button wire:click="cancelDelete" type="button"
                            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-neutral-500 dark:bg-neutral-800 dark:text-gray-200 dark:hover:bg-neutral-600">
                        {{ __('permission-registry::Cancel') }}
                    </button>
                    <button wire:click="deletePermission" type="button"
                            class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                        {{ __('permission-registry::Delete') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
