<div>
    <div class="mb-4 flex flex-col sm:flex-row justify-between gap-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <input wire:model.live="search" type="text" placeholder="{{ __('permission-registry::Search') }}"
                   class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">

            <select wire:model.live="service" class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value="">{{ __('permission-registry::All services') }}</option>
                @foreach($services as $serviceName)
                    <option value="{{ $serviceName }}">{{ $serviceName }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-2">
            <span>{{ __('permission-registry::Show') }}:</span>
            <select wire:model.live="perPage" class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ __('permission-registry::Service') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ __('permission-registry::Name') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ __('permission-registry::Description') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ __('permission-registry::Tags') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ __('permission-registry::Actions') }}
                </th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            @foreach($permissions as $permission)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $permission->service }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $permission->name }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ Str::limit($permission->description, 50) }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        @if($permission->tags)
                            <div class="flex flex-wrap gap-1">
                                @foreach($permission->tags as $tag)
                                    <span class="px-2 py-1 text-xs bg-gray-100 rounded-full">{{ $tag }}</span>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('permission-registry::permissions.show', $permission) }}"
                           class="text-indigo-600 hover:text-indigo-900">{{ __('permission-registry::View') }}</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="p-4">
            {{ $permissions->links() }}
        </div>
    </div>
</div>
