@props(['text', 'align' => 'center', 'dynamicText' => null])

<div
    {{ $attributes->class('relative') }}
    x-data="{
        open: false,
        position: '',
        show() {
            this.open = true;

            this.$nextTick(() => {
                const anchor = this.$el.getBoundingClientRect();
                const tooltip = this.$refs.tooltip;
                const gap = 8;
                const pageMargin = 8;
                let left = @js($align === 'right')
                    ? anchor.right - tooltip.offsetWidth
                    : anchor.left + ((anchor.width - tooltip.offsetWidth) / 2);
                let top = anchor.top - tooltip.offsetHeight - gap;

                left = Math.max(pageMargin, Math.min(left, window.innerWidth - tooltip.offsetWidth - pageMargin));

                if (top < pageMargin) {
                    top = anchor.bottom + gap;
                }

                this.position = `left: ${left}px; top: ${top}px;`;
            });
        },
    }"
    x-on:mouseenter="show()"
    x-on:mouseleave="open = false"
    x-on:focusin="show()"
    x-on:focusout="open = false"
>
    {{ $slot }}

    <template x-teleport="body">
        <div
            class="pointer-events-none fixed z-[100] overflow-hidden whitespace-nowrap rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs text-gray-600 shadow-lg"
            x-cloak
            x-show="open"
            x-bind:style="position"
            x-ref="tooltip"
            x-transition:enter="transition ease-out duration-200 transform"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-out duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            role="tooltip"
        >
            @if ($dynamicText)
                <span x-text="{{ $dynamicText }}">{{ $text }}</span>
            @else
                {{ $text }}
            @endif
        </div>
    </template>
</div>
