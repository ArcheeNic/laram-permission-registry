@php
    $statusValue = is_string($status) ? $status : $status->value ?? $status;
    $badgeClasses = match($statusValue) {
        'granted' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'awaiting_approval' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        'failed', 'partially_granted', 'partially_revoked' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
        'granting', 'revoking', 'pending' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        'revoked' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    };
    $statusEnum = is_string($status)
        ? \ArcheeNic\PermissionRegistry\Enums\GrantedPermissionStatus::tryFrom($status)
        : $status;
@endphp
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClasses }}">
    {{ $statusEnum?->label() ?? $statusValue }}
</span>
