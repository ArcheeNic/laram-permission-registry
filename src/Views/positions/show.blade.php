<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('permission-registry::Position Details') }}: {{ $position->name }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('permission-registry::positions.edit', $position) }}"
                   class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    {{ __('permission-registry::Edit') }}
                </a>
                <a href="{{ route('permission-registry::positions.index') }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-neutral-800 border border-gray-300 dark:border-neutral-600 rounded-md hover:bg-gray-50 dark:hover:bg-neutral-800">
                    {{ __('permission-registry::Back to Positions') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('permission-registry::Position Information') }}</h3>
                    <div class="bg-gray-50 dark:bg-neutral-800 p-4 rounded-md">
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('permission-registry::Name') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $position->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('permission-registry::Parent Position') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    @if($position->parent)
                                        <a href="{{ route('permission-registry::positions.show', $position->parent) }}" class="text-blue-600 dark:text-blue-300 hover:text-blue-900">
                                            {{ $position->parent->name }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('permission-registry::Description') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $position->description ?: '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                @if($position->children->isNotEmpty())
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('permission-registry::Child Positions') }}</h3>
                        <div class="bg-gray-50 dark:bg-neutral-800 p-4 rounded-md">
                            <ul class="divide-y divide-gray-200 dark:divide-neutral-700">
                                @foreach($position->children as $child)
                                    <li class="py-3">
                                        <a href="{{ route('permission-registry::positions.show', $child) }}" class="text-blue-600 dark:text-blue-300 hover:text-blue-900">
                                            {{ $child->name }}
                                        </a>
                                        @if($child->description)
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($child->description, 100) }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('permission-registry::Permissions') }}</h3>

                    @if($position->permissions->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('permission-registry::No direct permissions assigned to this position') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                                <thead class="bg-gray-50 dark:bg-neutral-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('permission-registry::Service') }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('permission-registry::Name') }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('permission-registry::Description') }}
                                    </th>
                                </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-neutral-800 divide-y divide-gray-200 dark:divide-neutral-700">
                                @foreach($position->permissions as $permission)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $permission->service }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            <a href="{{ route('permission-registry::permissions.show', $permission) }}" class="text-blue-600 dark:text-blue-300 hover:text-blue-900">
                                                {{ $permission->name }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                            {{ Str::limit($permission->description, 100) ?: '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('permission-registry::Permission Groups') }}</h3>

                    @if($position->groups->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('permission-registry::No permission groups assigned to this position') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                                <thead class="bg-gray-50 dark:bg-neutral-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('permission-registry::Name') }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ __('permission-registry::Description') }}
                                    </th>
                                </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-neutral-800 divide-y divide-gray-200 dark:divide-neutral-700">
                                @foreach($position->groups as $group)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            <a href="{{ route('permission-registry::groups.show', $group) }}" class="text-blue-600 dark:text-blue-300 hover:text-blue-900">
                                                {{ $group->name }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                            {{ Str::limit($group->description, 100) ?: '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
