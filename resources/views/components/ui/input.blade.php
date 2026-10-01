@props([
    'autocomplete' => null,
    'disabled' => false,
    'error' => null,
    'label',
    'name',
    'placeholder' => null,
    'required' => false,
    'type' => 'text',
    'value' => null,
])

@php($errorMessage = $error ?: $errors->first($name))

<div>
    <label class="block text-sm font-medium mb-1" for="{{ $name }}">
        {{ $label }}
        @if ($required)
            <span class="text-red-500" aria-hidden="true">*</span>
        @endif
    </label>
    <input
        {{ $attributes->class([
            'form-input w-full',
            'border-red-300' => $errorMessage,
            'dark:disabled:placeholder:text-gray-600 disabled:border-gray-200 dark:disabled:border-gray-700 disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:text-gray-400 dark:disabled:text-gray-600 disabled:cursor-not-allowed shadow-none' => $disabled,
        ]) }}
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($required) required aria-required="true" @endif
        @if ($disabled) disabled @endif
        @if ($errorMessage) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
    >
    @if ($errorMessage)
        <p class="text-xs mt-1 text-red-500" id="{{ $name }}-error">{{ $errorMessage }}</p>
    @endif
</div>
