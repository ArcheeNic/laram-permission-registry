@php
    $matchStatus = $row->match_status instanceof \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus
        ? $row->match_status
        : \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::tryFrom($row->match_status);

    $badgeClasses = match($matchStatus) {
        \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::NEW => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
        \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::CHANGED => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
        \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::EXISTS => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::MISSING => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
        default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
    };

    $statusLabel = match($matchStatus) {
        \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::NEW => __('permission-registry::messages.import.status_new'),
        \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::CHANGED => __('permission-registry::messages.import.status_changed'),
        \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::EXISTS => __('permission-registry::messages.import.status_exists'),
        \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::MISSING => __('permission-registry::messages.import.status_missing'),
        default => '—',
    };

    $actionTooltip = match($matchStatus) {
        \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::NEW => __('permission-registry::messages.import.action_new'),
        \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::CHANGED => __('permission-registry::messages.import.action_changed'),
        \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::EXISTS => __('permission-registry::messages.import.action_exists'),
        \ArcheeNic\PermissionRegistry\Enums\ImportMatchStatus::MISSING => __('permission-registry::messages.import.action_missing'),
        default => '',
    };
@endphp
<span class="relative group/status inline-flex cursor-help">
    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeClasses }}">
        {{ $statusLabel }}
    </span>
    <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 text-xs text-white bg-gray-900 dark:bg-gray-700 rounded-lg shadow-lg
                 w-56 text-center leading-relaxed
                 opacity-0 invisible group-hover/status:opacity-100 group-hover/status:visible
                 transition-all duration-150 pointer-events-none z-50">
        {{ $actionTooltip }}
        <span class="absolute top-full left-1/2 -translate-x-1/2 -mt-px border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></span>
    </span>
</span>
