<div>
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('permission-registry::Users with this Permission') }}
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $users->total() }})</span>
        </h3>
        <input wire:model.live="search" type="text"
               placeholder="{{ __('permission-registry::Search users') }}"
               class="rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-neutral-700 dark:text-gray-200
                      focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
    </div>

    @if($users->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('permission-registry::No users have this permission') }}
        </p>
    @else
        <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-sm sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-neutral-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ __('permission-registry::Name') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ __('permission-registry::Status') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ __('permission-registry::Granted at') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ __('permission-registry::Expires at') }}
                    </th>
                </tr>
                </thead>
                <tbody class="bg-white dark:bg-neutral-800 divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($users as $grant)
                    <tr class="hover:bg-gray-50 dark:hover:bg-neutral-700/50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                            @if($grant->user)
                                <a href="{{ route('permission-registry::users.show', $grant->user) }}"
                                   class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                    {{ $grant->user->name }}
                                </a>
                            @else
                                <span class="text-gray-400 dark:text-gray-500">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <x-pr::permission-status-badge :status="$grant->status" />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $grant->granted_at?->format('d.m.Y H:i') ?? '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $grant->expires_at?->format('d.m.Y H:i') ?? '—' }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div class="p-4 border-t dark:border-gray-700">
                {{ $users->links() }}
            </div>
        </div>
    @endif
</div>
