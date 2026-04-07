@props(['isProcessing' => false, 'completedPermissionsWithErrors' => [], 'processingPermissions' => []])

<div class="mt-4 p-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded text-xs">
    <strong>Debug:</strong> 
    isProcessing: {{ $isProcessing ? 'true' : 'false' }} | 
    completedPermissionsWithErrors: {{ count($completedPermissionsWithErrors) }} | 
    processingPermissions: {{ count($processingPermissions) }}
    
    @if(!empty($completedPermissionsWithErrors))
        <div class="mt-2 p-2 bg-white dark:bg-neutral-700 rounded">
            <strong>Errors data:</strong>
            <pre class="text-xs overflow-auto max-h-40">{{ json_encode($completedPermissionsWithErrors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    @endif
</div>
