@props(['status' => null])

@if($status)
    @php
        $classes = match($status) {
            'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 animate-pulse',
            'approved' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
            'rejected' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
            'expired' => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
            'cancelled' => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
            default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        };
        $label = __("permission-registry::approvals.status.{$status}");
    @endphp
    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $classes }}">
        {{ $label }}
    </span>
@endif
