@props([
    'icon' => null,
    'size' => 'sm',
    'variant' => 'neutral',
])

@php
    $variantClass = match ($variant) {
        'primary' => 'bg-violet-500/20 text-violet-700',
        'success' => 'bg-green-500/20 text-green-700',
        'warning' => 'bg-yellow-500/20 text-yellow-700',
        'danger' => 'bg-red-500/20 text-red-700',
        'info' => 'bg-blue-500/20 text-blue-600',
        'dark' => 'bg-gray-700 text-gray-100',
        default => 'bg-gray-400/20 text-gray-500',
    };
@endphp

<span {{ $attributes->class([
    'inline-flex items-center font-medium rounded-full text-center',
    $variantClass,
    'text-xs px-2 py-0.5' => $size === 'xs',
    'text-sm px-2.5 py-1' => $size !== 'xs',
]) }}>
    @if ($icon === 'bolt')
        <svg class="w-3 h-3 shrink-0 fill-current text-yellow-500 mr-1" viewBox="0 0 12 12" aria-hidden="true">
            <path d="M11.953 4.29a.5.5 0 0 0-.454-.292H6.14L6.984.62A.5.5 0 0 0 6.12.173l-6 7a.5.5 0 0 0 .379.825h5.359l-.844 3.38a.5.5 0 0 0 .864.445l6-7a.5.5 0 0 0 .075-.534Z" />
        </svg>
    @endif
    {{ $slot }}
</span>
