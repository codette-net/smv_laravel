@props([
    'size' => 'default',
    'type' => 'button',
    'variant' => 'primary',
])

@php
    $sizeClass = match ($size) {
        'xs' => 'btn-xs',
        'sm' => 'btn-sm',
        'lg' => 'btn-lg',
        default => 'btn',
    };

    $variantClass = match ($variant) {
        'brand' => 'bg-blue-600 text-white hover:bg-blue-700 focus-visible:outline-blue-600',
        'secondary' => 'bg-white border-gray-200 hover:border-gray-300 text-gray-800',
        'tertiary' => 'bg-white border-gray-200 hover:border-gray-300 text-blue-700',
        'danger' => 'bg-red-500 hover:bg-red-600 text-white',
        'success' => 'bg-green-500 hover:bg-green-600 text-white',
        default => 'bg-gray-900 text-gray-100 hover:bg-gray-800',
    };
@endphp

<button {{ $attributes->class([$sizeClass, $variantClass, 'disabled:border-gray-200 disabled:bg-white disabled:text-gray-300 disabled:cursor-not-allowed'])->merge(['type' => $type]) }}>
    {{ $slot }}
</button>
