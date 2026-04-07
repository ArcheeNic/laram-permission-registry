@props([
    'title',
    'description',
])

<span class="relative inline-flex align-middle ml-1" x-data="{ open: false }" x-id="['hint']">
    <button
        type="button"
        class="inline-flex h-4 w-4 items-center justify-center rounded-full border border-gray-400 text-[10px] font-semibold text-gray-600 transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-neutral-500 dark:text-gray-300 dark:hover:bg-neutral-700"
        @click.prevent.stop="open = !open"
        :aria-expanded="open.toString()"
        :aria-controls="$id('hint')"
        :aria-describedby="$id('hint')"
        aria-label="{{ $title }}"
    >
        ?
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.away="open = false"
        @keydown.escape.window="open = false"
        :id="$id('hint')"
        role="tooltip"
        class="absolute right-0 top-6 z-50 w-72 max-w-[calc(100vw-2rem)] rounded-md border border-gray-200 bg-white p-3 shadow-lg sm:left-0 sm:right-auto dark:border-neutral-700 dark:bg-neutral-800"
        style="display: none;"
    >
        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
            {{ $title }}
        </p>
        <p class="mt-1 text-xs leading-5 text-gray-600 dark:text-gray-300">
            {{ $description }}
        </p>
    </div>
</span>
