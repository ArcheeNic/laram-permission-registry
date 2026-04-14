<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('permission-registry::Permission Details') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('permission-registry::permissions.edit', $permission) }}"
                   class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    {{ __('permission-registry::Edit') }}
                </a>
                <form action="{{ route('permission-registry::permissions.copy', $permission) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                        {{ __('permission-registry::Copy') }}
                    </button>
                </form>
                <form action="{{ route('permission-registry::permissions.destroy', $permission) }}" method="POST"
                      data-confirm-message="{{ __('permission-registry::Are you sure you want to delete this permission?') }}"
                      onsubmit="return confirm(this.dataset.confirmMessage)">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">
                        {{ __('permission-registry::Delete') }}
                    </button>
                </form>
                <a href="{{ route('permission-registry::permissions.index') }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-neutral-800 border border-gray-300 dark:border-neutral-600 rounded-md hover:bg-gray-50 dark:hover:bg-neutral-700">
                    {{ __('permission-registry::Back to Permissions') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                @if (session('error'))
                    <div class="mb-4 rounded-md border-l-4 border-red-500 bg-red-100 p-4 text-red-700 dark:border-red-400 dark:bg-red-900/30 dark:text-red-300" role="alert">
                        <p>{{ session('error') }}</p>
                    </div>
                @endif

                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('permission-registry::Permission Information') }}</h3>
                    <div class="bg-gray-50 p-4 rounded-md">
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __('permission-registry::Service') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $permission->service }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __('permission-registry::Name') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $permission->name }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500">{{ __('permission-registry::Description') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $permission->description ?: '—' }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500">{{ __('permission-registry::Tags') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    @if(!empty($permission->tags))
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($permission->tags as $tag)
                                                <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">{{ $tag }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __('permission-registry::Auto Grant') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    @if($permission->auto_grant)
                                        <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">{{ __('permission-registry::Yes') }}</span>
                                    @else
                                        <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full">{{ __('permission-registry::No') }}</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __('permission-registry::Auto Revoke') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    @if($permission->auto_revoke)
                                        <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">{{ __('permission-registry::Yes') }}</span>
                                    @else
                                        <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full">{{ __('permission-registry::No') }}</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('permission-registry::Permission Fields') }}</h3>

                    @if($permission->fields->isEmpty())
                        <p class="text-sm text-gray-500">{{ __('permission-registry::No fields defined for this permission') }}</p>
                    @else
                        <div class="bg-gray-50 p-4 rounded-md">
                            <ul class="divide-y divide-gray-200">
                                @foreach($permission->fields as $field)
                                    <li class="py-3 flex justify-between">
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">{{ $field->name }}</span>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-500">{{ __('permission-registry::Default') }}: {{ $field->default_value ?: '—' }}</span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('permission-registry::Groups Using This Permission') }}</h3>

                    @if($permission->groups->isEmpty())
                        <p class="text-sm text-gray-500">{{ __('permission-registry::This permission is not included in any groups') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('permission-registry::Name') }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('permission-registry::Description') }}
                                    </th>
                                </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($permission->groups as $group)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <a href="{{ route('permission-registry::groups.show', $group) }}" class="text-blue-600 hover:text-blue-900">
                                                {{ $group->name }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ Str::limit($group->description, 100) ?: '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Users with this Permission --}}
                <div class="mb-8">
                    @livewire('permission-registry::permission-users', ['permissionId' => $permission->id])
                </div>

                {{-- Approval Policy --}}
                @livewire('permission-registry::approval-policy-manager', ['permissionId' => $permission->id])

                <div class="mt-8">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('permission-registry::Positions Using This Permission') }}</h3>

                    @if($permission->positions->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('permission-registry::This permission is not assigned to any positions') }}</p>
                    @else
                        <div class="bg-gray-50 dark:bg-neutral-800 p-4 rounded-md">
                            <ul class="space-y-2">
                                @foreach($permission->positions as $position)
                                    <li class="py-2 px-3 bg-white dark:bg-neutral-700 rounded-md">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <a href="{{ route('permission-registry::positions.show', $position) }}" class="text-sm font-medium text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                    {{ $position->name }}
                                                </a>
                                                @if($position->parent)
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        <span class="font-medium">{{ __('permission-registry::Parent Position') }}:</span>
                                                        <a href="{{ route('permission-registry::positions.show', $position->parent) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                            {{ $position->parent->name }}
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                            @if($position->description)
                                                <div class="text-sm text-gray-500 dark:text-gray-400 ml-4">
                                                    {{ Str::limit($position->description, 80) }}
                                                </div>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
