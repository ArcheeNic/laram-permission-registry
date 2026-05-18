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
            <input wire:model.live.debounce.300ms="search" type="text"
                   placeholder="{{ __('permission-registry::Search') }}"
                   class="rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200">

            <select wire:model.live="service"
                    class="rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200">
                <option value="">{{ __('permission-registry::All services') }}</option>
                @foreach($services as $s)
                    <option value="{{ $s }}">{{ $s }}</option>
                @endforeach
            </select>

            <select wire:model.live="kind"
                    class="rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200">
                <option value="">{{ __('permission-registry::All kinds') }}</option>
                @foreach($kinds as $k)
                    <option value="{{ $k }}">{{ $k }}</option>
                @endforeach
            </select>

            <select wire:model.live="presence"
                    class="rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200">
                <option value="all">{{ __('permission-registry::All') }}</option>
                <option value="present">{{ __('permission-registry::Present') }}</option>
                <option value="missing">{{ __('permission-registry::Disappeared') }}</option>
            </select>

            <select wire:model.live="ignored"
                    class="rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200">
                <option value="active">{{ __('permission-registry::Active only') }}</option>
                <option value="all">{{ __('permission-registry::All (incl. ignored)') }}</option>
                <option value="ignored">{{ __('permission-registry::Ignored only') }}</option>
            </select>

            <div class="ml-auto flex gap-2">
                <button wire:click="openCreate"
                        type="button"
                        class="rounded-md bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">
                    {{ __('permission-registry::Add resource') }}
                </button>
                <button wire:click="sync('{{ $service }}')"
                        type="button"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
                    {{ __('permission-registry::Sync now') }}
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-sm sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-neutral-700">
            <tr>
                <th class="px-4 py-2 text-left text-xs uppercase text-gray-500 dark:text-gray-400">{{ __('permission-registry::Service') }}</th>
                <th class="px-4 py-2 text-left text-xs uppercase text-gray-500 dark:text-gray-400">{{ __('permission-registry::Kind') }}</th>
                <th class="px-4 py-2 text-left text-xs uppercase text-gray-500 dark:text-gray-400">{{ __('permission-registry::External ID') }}</th>
                <th class="px-4 py-2 text-left text-xs uppercase text-gray-500 dark:text-gray-400">{{ __('permission-registry::Name') }}</th>
                <th class="px-4 py-2 text-center text-xs uppercase text-gray-500 dark:text-gray-400">{{ __('permission-registry::Grants') }}</th>
                <th class="px-4 py-2 text-left text-xs uppercase text-gray-500 dark:text-gray-400">{{ __('permission-registry::Synced at') }}</th>
                <th class="px-4 py-2 text-center text-xs uppercase text-gray-500 dark:text-gray-400">{{ __('permission-registry::Status') }}</th>
                <th class="px-4 py-2 text-right text-xs uppercase text-gray-500 dark:text-gray-400">{{ __('permission-registry::Actions') }}</th>
            </tr>
            </thead>
            <tbody class="bg-white dark:bg-neutral-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($resources as $resource)
                @php $orphaned = !$resource->present_in_source && $resource->granted_permissions_count > 0; @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700/50 {{ $orphaned ? 'bg-rose-50 dark:bg-rose-900/20' : '' }}">
                    <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $resource->service }}</td>
                    <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $resource->kind }}</td>
                    <td class="px-4 py-2 text-sm font-mono text-gray-700 dark:text-gray-300">{{ $resource->external_id }}</td>
                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">
                        {{ $resource->name }}
                        @if($orphaned)
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wide bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300" title="{{ __('permission-registry::Resource disappeared from source but active grants remain') }}">
                                {{ __('permission-registry::orphaned grants') }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-center text-sm text-gray-700 dark:text-gray-300">{{ $resource->granted_permissions_count }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ optional($resource->synced_at)->format('Y-m-d H:i') ?? '—' }}</td>
                    <td class="px-4 py-2 text-center text-xs">
                        <div class="inline-flex flex-wrap justify-center gap-1">
                            @if($resource->present_in_source)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">{{ __('permission-registry::present') }}</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">{{ __('permission-registry::missing') }}</span>
                            @endif
                            @if($resource->is_ignored)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium bg-gray-200 text-gray-700 dark:bg-neutral-700 dark:text-gray-300">{{ __('permission-registry::ignored') }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-2 text-right">
                        <div class="inline-flex flex-wrap justify-end gap-1">
                            <button wire:click="syncOne({{ $resource->id }})"
                                    type="button"
                                    wire:loading.attr="disabled"
                                    wire:target="syncOne({{ $resource->id }})"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md bg-sky-600 text-white hover:bg-sky-700 disabled:opacity-50">
                                {{ __('permission-registry::Sync this') }}
                            </button>
                            <button wire:click="openEdit({{ $resource->id }})"
                                    type="button"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-neutral-700 dark:text-gray-200 dark:hover:bg-neutral-600">
                                {{ __('permission-registry::Edit') }}
                            </button>
                            <button wire:click="toggleIgnore({{ $resource->id }})"
                                    type="button"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md {{ $resource->is_ignored ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-slate-500 text-white hover:bg-slate-600' }}">
                                {{ $resource->is_ignored ? __('permission-registry::Un-ignore') : __('permission-registry::Ignore') }}
                            </button>
                            @if($resource->granted_permissions_count === 0)
                                <button wire:click="deleteResource({{ $resource->id }})"
                                        type="button"
                                        wire:confirm="{{ __('permission-registry::Are you sure?') }}"
                                        class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md bg-rose-600 text-white hover:bg-rose-700">
                                    {{ __('permission-registry::Delete') }}
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('permission-registry::No resources') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t dark:border-gray-700">
            {{ $resources->links() }}
        </div>
    </div>

    @if($this->showFormModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" wire:click="closeForm"></div>

                <div class="relative w-full max-w-lg rounded-lg bg-white dark:bg-neutral-800 shadow-xl">
                    <div class="border-b border-gray-200 dark:border-neutral-700 px-6 py-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            {{ $this->editingResourceId ? __('permission-registry::Edit resource') : __('permission-registry::Add resource') }}
                        </h3>
                    </div>

                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('permission-registry::Service') }}</label>
                            <input wire:model="formService" type="text" placeholder="b24 / telegram / gsheet"
                                   class="mt-1 w-full rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200">
                            @error('formService') <p class="text-xs text-red-500 dark:text-red-300 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('permission-registry::Kind') }}</label>
                            <input wire:model="formKind" type="text" placeholder="department / chat / sheet"
                                   class="mt-1 w-full rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200">
                            @error('formKind') <p class="text-xs text-red-500 dark:text-red-300 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('permission-registry::External ID') }}</label>
                            <input wire:model="formExternalId" type="text"
                                   class="mt-1 w-full rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200 font-mono">
                            @error('formExternalId') <p class="text-xs text-red-500 dark:text-red-300 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('permission-registry::Name') }}</label>
                            <input wire:model="formName" type="text"
                                   class="mt-1 w-full rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200">
                            @error('formName') <p class="text-xs text-red-500 dark:text-red-300 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('permission-registry::Metadata') }}
                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('permission-registry::(optional, key=value per line or JSON)') }}</span>
                            </label>
                            <textarea wire:model="formMetadata" rows="3"
                                      placeholder="parent_external_id=12&#10;space_id=abc"
                                      class="mt-1 w-full rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200 font-mono text-xs"></textarea>
                            @error('formMetadata') <p class="text-xs text-red-500 dark:text-red-300 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-neutral-700/50 px-6 py-3 flex justify-end gap-2">
                        <button wire:click="closeForm" type="button"
                                class="rounded-md bg-white dark:bg-neutral-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-neutral-600 hover:bg-gray-50 dark:hover:bg-neutral-600">
                            {{ __('permission-registry::Cancel') }}
                        </button>
                        <button wire:click="saveResource" type="button"
                                wire:loading.attr="disabled"
                                wire:target="saveResource"
                                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            {{ __('permission-registry::Save') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
