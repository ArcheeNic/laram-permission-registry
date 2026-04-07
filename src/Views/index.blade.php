<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('permission-registry::Permissions Registry') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @php
                $pendingRevocationsCount = \ArcheeNic\PermissionRegistry\Models\VirtualUser::query()
                    ->where('status', \ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus::DEACTIVATED->value)
                    ->whereHas('grantedPermissions', fn ($query) => $query->where('enabled', true))
                    ->count();
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Раздел прав доступа -->
                <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-xl sm:rounded-lg p-6 hover:shadow-2xl transition-shadow duration-300">
                    <div class="flex items-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <h3 class="ml-3 text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('permission-registry::Permissions') }}</h3>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('permission-registry::Manage permissions for different services and resources') }}</p>
                    <a href="{{ route('permission-registry::permissions.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                        {{ __('permission-registry::Manage Permissions') }}
                    </a>
                </div>

                <!-- Раздел полей доступа -->
                <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-xl sm:rounded-lg p-6 hover:shadow-2xl transition-shadow duration-300">
                    <div class="flex items-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <h3 class="ml-3 text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('permission-registry::Fields') }}</h3>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('permission-registry::Create and manage fields for permissions') }}</p>
                    <a href="{{ route('permission-registry::fields.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                        {{ __('permission-registry::Manage Fields') }}
                    </a>
                </div>

                <!-- Раздел групп доступа -->
                <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-xl sm:rounded-lg p-6 hover:shadow-2xl transition-shadow duration-300">
                    <div class="flex items-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <h3 class="ml-3 text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('permission-registry::Groups') }}</h3>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('permission-registry::Group permissions for easier management') }}</p>
                    <a href="{{ route('permission-registry::groups.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 transition-colors">
                        {{ __('permission-registry::Manage Groups') }}
                    </a>
                </div>

                <!-- Раздел триггеров -->
                <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-xl sm:rounded-lg p-6 hover:shadow-2xl transition-shadow duration-300">
                    <div class="flex items-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <h3 class="ml-3 text-lg font-medium text-gray-900 dark:text-gray-100">Триггеры</h3>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Автоматизация действий при выдаче и отзыве прав доступа</p>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('permission-registry::triggers.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 transition-all">
                            Управление триггерами
                        </a>
                        <a href="{{ route('permission-registry::hr-triggers.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-purple-500 to-fuchsia-600 hover:from-purple-600 hover:to-fuchsia-700 transition-all">
                            {{ __('permission-registry::messages.hr_triggers') }}
                        </a>
                    </div>
                </div>

                <!-- Раздел должностей -->
                <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-xl sm:rounded-lg p-6 hover:shadow-2xl transition-shadow duration-300">
                    <div class="flex items-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <h3 class="ml-3 text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('permission-registry::Positions') }}</h3>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('permission-registry::Manage hierarchical positions with inherited permissions') }}</p>
                    <a href="{{ route('permission-registry::positions.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 transition-colors">
                        {{ __('permission-registry::Manage Positions') }}
                    </a>
                </div>

                <!-- Раздел подтверждений -->
                <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-xl sm:rounded-lg p-6 hover:shadow-2xl transition-shadow duration-300">
                    <div class="flex items-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="ml-3 text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('permission-registry::Approvals') }}</h3>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('permission-registry::Pending Approvals') }}</p>
                    <a href="{{ route('permission-registry::approvals.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-amber-600 hover:bg-amber-700 transition-colors">
                        {{ __('permission-registry::Approvals') }}
                    </a>
                </div>

                <!-- Раздел пользователей -->
                <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-xl sm:rounded-lg p-6 hover:shadow-2xl transition-shadow duration-300">
                    <div class="flex items-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <h3 class="ml-3 text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('permission-registry::Users') }}</h3>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('permission-registry::Assign permissions, groups and positions to users') }}</p>
                    <a href="{{ route('permission-registry::users.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 transition-colors">
                        {{ __('permission-registry::Manage Users') }}
                    </a>
                </div>

                <!-- Раздел импорта -->
                <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-xl sm:rounded-lg p-6 hover:shadow-2xl transition-shadow duration-300">
                    <div class="flex items-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <h3 class="ml-3 text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('permission-registry::messages.import.title') }}</h3>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('permission-registry::messages.import.description') }}</p>
                    <a href="{{ route('permission-registry::imports.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-cyan-600 hover:bg-cyan-700 transition-colors">
                        {{ __('permission-registry::messages.import.manage') }}
                    </a>
                </div>

                <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-xl sm:rounded-lg p-6 hover:shadow-2xl transition-shadow duration-300 border border-orange-200 dark:border-orange-800/40">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 014-4h7m0 0l-3-3m3 3l-3 3M5 7h10M5 12h4M5 17h4" />
                            </svg>
                            <h3 class="ml-3 text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('permission-registry::messages.pending_revocations') }}</h3>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300">
                            {{ $pendingRevocationsCount }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('permission-registry::messages.pending_revocations_desc') }}</p>
                    <a href="{{ route('permission-registry::pending-revocations.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-orange-600 hover:bg-orange-700 transition-colors">
                        {{ __('permission-registry::messages.open_pending_revocations') }}
                    </a>
                </div>
            </div>

            {{-- Self-service --}}
            <div class="mt-8">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('permission-registry::messages.self_service') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-xl sm:rounded-lg p-6 hover:shadow-2xl transition-shadow duration-300">
                        <div class="flex items-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="ml-3 text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('permission-registry::messages.my_permissions') }}</h3>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('permission-registry::messages.my_permissions_desc') }}</p>
                        <a href="{{ route('permission-registry::my.permissions') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-teal-600 hover:bg-teal-700 transition-colors">
                            {{ __('permission-registry::messages.view_my_permissions') }}
                        </a>
                    </div>

                    <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-xl sm:rounded-lg p-6 hover:shadow-2xl transition-shadow duration-300">
                        <div class="flex items-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <h3 class="ml-3 text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('permission-registry::messages.my_requests') }}</h3>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('permission-registry::messages.my_requests_desc') }}</p>
                        <a href="{{ route('permission-registry::my.requests') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-cyan-600 hover:bg-cyan-700 transition-colors">
                            {{ __('permission-registry::messages.view_my_requests') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
