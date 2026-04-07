@props([
    'show' => false,
    'maxWidth' => '7xl',
])

<div x-data="{ show: {{ $show ? 'true' : 'false' }} }"
     x-show="show"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @keydown.escape.window="{{ $attributes->has('wire:model') ? '$wire.' . $attributes->get('wire:model') . ' = false' : '' }}"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    
    <!-- Фон с blur -->
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"
         @click="{{ $attributes->has('wire:model') ? '$wire.' . $attributes->get('wire:model') . ' = false' : '' }}"></div>
    
    <!-- Модальное окно -->
    <div class="flex min-h-screen items-center justify-center p-4">
        <div x-show="show"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95 translate-y-10"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-10"
             class="relative w-full max-w-{{ $maxWidth }} bg-white dark:bg-neutral-900 rounded-2xl shadow-2xl overflow-hidden"
             @click.stop>
            {{ $slot }}
        </div>
    </div>
</div>
