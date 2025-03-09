<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('permission-registry::Permission Details') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('permission-registry::permissions.edit', $permission) }}"
                   class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    {{ __('permission-registry::Edit') }}
                </a>
                <a href="{{ route('permission-registry::permissions.index') }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    {{ __('permission-registry::Back to Permissions') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
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

                <div>
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
            </div>
        </div>
    </div>
</x-app-layout>
