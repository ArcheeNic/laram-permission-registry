@props(['status' => null, 'statusMessage' => null])

@if($status)
    @php
        $statusClasses = match($status) {
            'awaiting_approval' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 animate-pulse',
            'pending' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 animate-pulse',
            'granting' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 animate-pulse',
            'granted' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
            'revoking' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300 animate-pulse',
            'revoked' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
            'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
            'partially_granted' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
            'rejected' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
            'manual_pending' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 animate-pulse',
            'declared' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
            default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        };
        $statusLabel = match($status) {
            'awaiting_approval' => __('permission-registry::Awaiting Approval'),
            'pending' => __('permission-registry::Pending'),
            'granting' => __('permission-registry::Granting'),
            'granted' => __('permission-registry::Granted'),
            'revoking' => __('permission-registry::Revoking'),
            'revoked' => __('permission-registry::Revoked'),
            'failed' => __('permission-registry::Failed'),
            'partially_granted' => __('permission-registry::Partially Granted'),
            'rejected' => __('permission-registry::Rejected'),
            'manual_pending' => __('permission-registry::Manual Pending'),
            'declared' => __('permission-registry::Declared'),
            default => $status,
        };
    @endphp
    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClasses }}"
          title="{{ $statusMessage ?? '' }}"
          aria-label="{{ __('permission-registry::Permission Status') }}: {{ $statusLabel }}">
        {{ $statusLabel }}
    </span>
@endif
