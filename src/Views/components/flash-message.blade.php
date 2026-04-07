@props(['type' => 'success', 'message' => null])

@if($message)
    @php
        $bgClass = match($type) {
            'success' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200',
            'error' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200',
            'warning' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200',
            default => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200',
        };
    @endphp
    <div class="mb-4 p-4 {{ $bgClass }} border rounded-lg">
        {{ $message }}
    </div>
@endif
