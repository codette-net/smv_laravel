@props([
    'description',
    'id' => 'status-modal',
    'open' => false,
    'title',
])

<div
    x-data="{ open: @js((bool) $open) }"
    x-init="if (open) { $nextTick(() => $refs.closeButton.focus()) }"
    x-on:keydown.escape.window="open = false"
>
    <div
        @class(['fixed inset-0 z-50 bg-slate-900/30 transition-opacity', 'hidden' => ! $open])
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-out duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        aria-hidden="true"
        x-cloak
    ></div>

    <div
        @class(['fixed inset-0 z-50 flex items-center justify-center overflow-y-auto px-4 py-6 sm:px-6', 'hidden' => ! $open])
        id="{{ $id }}"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $id }}-title"
        aria-describedby="{{ $id }}-description"
        x-show="open"
        x-transition:enter="transition ease-in-out duration-200"
        x-transition:enter-start="translate-y-4 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in-out duration-100"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-4 opacity-0"
        x-cloak
    >
        <div class="w-full max-w-lg overflow-hidden rounded-xl bg-white shadow-xl" x-on:click.outside="open = false">
            <div class="flex gap-4 p-6" aria-live="polite" role="status">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-green-50">
                    <svg class="fill-current text-green-600" width="18" height="18" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zM7 11.4 3.6 8 5 6.6l2 2 4-4L12.4 6 7 11.4z" />
                    </svg>
                </div>
                <div class="min-w-0 grow">
                    <h2 class="text-lg font-semibold text-slate-900" id="{{ $id }}-title">{{ $title }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600" id="{{ $id }}-description">{{ $description }}</p>
                    <div class="mt-6 flex justify-end">
                        <x-ui.button x-ref="closeButton" variant="brand" x-on:click="open = false">Sluiten</x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($open)
        <noscript>
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-900" role="status">
                <strong>{{ $title }}</strong>
                <span class="mt-1 block">{{ $description }}</span>
            </div>
        </noscript>
    @endif
</div>
