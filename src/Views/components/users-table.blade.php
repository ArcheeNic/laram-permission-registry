@props([
    'users',
    'sortField' => 'created_at',
    'sortDirection' => 'desc',
    'bulkSelectedIds' => [],
    'currentPageAllSelected' => false,
])

@php
    $sortableColumns = [
        'id' => __('permission-registry::ID'),
        'name' => __('permission-registry::Name'),
    ];
@endphp

<div class="hidden md:block overflow-x-auto bg-white dark:bg-neutral-800 rounded-xl shadow-md border border-gray-200 dark:border-neutral-700">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
        <thead class="bg-gray-50 dark:bg-neutral-900">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">
                    <input type="checkbox"
                           @checked($currentPageAllSelected)
                           wire:click.stop="toggleBulkSelectAll([{{ implode(',', $users->pluck('id')->all()) }}])"
                           aria-label="{{ __('permission-registry::Select All') }}"
                           class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                </th>
                @foreach($sortableColumns as $field => $label)
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider group hover:bg-gray-100 dark:hover:bg-neutral-800 transition-colors"
                        aria-sort="{{ $sortField === $field ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                        <button type="button"
                                wire:click="$set('sortField', '{{ $field }}')"
                                class="inline-flex items-center gap-1 cursor-pointer select-none focus:outline-none focus:ring-2 focus:ring-blue-500 rounded {{ $sortField === $field ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400' }}"
                                aria-label="{{ $label }} — {{ __('permission-registry::Sort by') }}">
                            {{ $label }}
                            @if($sortField === $field)
                                @if($sortDirection === 'asc')
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                @else
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                @endif
                            @else
                                <svg class="w-3.5 h-3.5 opacity-0 group-hover:opacity-50 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                            @endif
                        </button>
                    </th>
                @endforeach
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                    {{ __('permission-registry::Email') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                    {{ __('permission-registry::Positions') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                    {{ __('permission-registry::Groups') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                    {{ __('permission-registry::Granted Permissions') }}
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
            @foreach($users as $user)
                @php
                    $nameParts = explode(' ', $user->name);
                    $initials = '';
                    foreach (array_slice($nameParts, 0, 2) as $part) {
                        $initials .= mb_substr($part, 0, 1);
                    }
                    $initials = mb_strtoupper($initials);

                    $gradients = [
                        'from-purple-500 via-pink-500 to-red-500',
                        'from-blue-500 via-cyan-500 to-teal-500',
                        'from-indigo-500 via-purple-500 to-pink-500',
                        'from-green-500 via-emerald-500 to-cyan-500',
                        'from-orange-500 via-red-500 to-pink-500',
                        'from-fuchsia-500 via-purple-500 to-indigo-500',
                        'from-rose-500 via-pink-500 to-purple-500',
                        'from-amber-500 via-orange-500 to-red-500',
                    ];
                    $gradient = $gradients[$user->id % count($gradients)];
                    $email = $user->email_for_display;
                    $grantedPermissions = $user->grantedPermissions;
                    $latestGrantedPermission = $grantedPermissions->first();
                    $latestGrantedAt = $latestGrantedPermission?->granted_at?->format('d.m.Y H:i');
                @endphp
                <tr wire:click="openEditModal({{ $user->id }})"
                    class="{{ $user->isActive() ? 'hover:bg-gray-50 dark:hover:bg-neutral-700' : 'opacity-70 hover:bg-amber-50 dark:hover:bg-amber-900/10' }} cursor-pointer transition-colors duration-150">
                    <td class="px-4 py-4 whitespace-nowrap" wire:click.stop>
                        <input type="checkbox"
                               @checked(in_array($user->id, $bulkSelectedIds, true))
                               wire:click.stop="toggleBulkSelect({{ $user->id }})"
                               aria-label="{{ __('permission-registry::messages.select_user') }} #{{ $user->id }}"
                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500 dark:text-gray-400">
                        {{ $user->id }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br {{ $gradient }} flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                {{ $initials }}
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</span>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $user->isActive() ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' }}">
                                    {{ $user->isActive() ? __('permission-registry::messages.active') : __('permission-registry::messages.deactivated') }}
                                </span>
                                @if(($user->pending_hr_conflicts_count ?? 0) > 0)
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                        {{ __('permission-registry::messages.requires_action') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($email)
                            <span class="text-sm text-gray-700 dark:text-gray-200">{{ $email }}</span>
                        @else
                            <span class="text-xs text-gray-400 dark:text-gray-500">&mdash;</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-wrap gap-1">
                            @foreach($user->positions->take(2) as $position)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                    @include('permission-registry::components.position-hierarchy-label', ['position' => $position])
                                </span>
                            @endforeach
                            @if($user->positions->count() > 2)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-neutral-700 text-gray-600 dark:text-gray-300">
                                    +{{ $user->positions->count() - 2 }}
                                </span>
                            @endif
                            @if($user->positions->isEmpty())
                                <span class="text-xs text-gray-400 dark:text-gray-500">&mdash;</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-wrap gap-1">
                            @foreach($user->groups->take(2) as $group)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300">
                                    {{ $group->name }}
                                </span>
                            @endforeach
                            @if($user->groups->count() > 2)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-neutral-700 text-gray-600 dark:text-gray-300">
                                    +{{ $user->groups->count() - 2 }}
                                </span>
                            @endif
                            @if($user->groups->isEmpty())
                                <span class="text-xs text-gray-400 dark:text-gray-500">&mdash;</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @if($grantedPermissions->isNotEmpty())
                            <div class="flex flex-wrap gap-1 mb-1.5">
                                @foreach($grantedPermissions->take(2) as $grantedPermission)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300">
                                        {{ $grantedPermission->permission->name }}
                                    </span>
                                @endforeach
                                @if($grantedPermissions->count() > 2)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-neutral-700 text-gray-600 dark:text-gray-300">
                                        +{{ $grantedPermissions->count() - 2 }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('permission-registry::Granted at') }}:
                                <span class="font-medium text-gray-700 dark:text-gray-200">{{ $latestGrantedAt ?: '—' }}</span>
                            </p>
                        @else
                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                {{ __('permission-registry::No permissions granted yet') }}
                            </span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Mobile cards fallback --}}
<div class="md:hidden grid grid-cols-1 gap-4">
    @foreach($users as $user)
        <x-pr::user-card :user="$user" :isSelected="in_array($user->id, $bulkSelectedIds, true)" />
    @endforeach
</div>
