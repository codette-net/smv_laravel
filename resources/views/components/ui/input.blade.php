@props(['name', 'label', 'type' => 'text'])

<div>
    <label class="mb-1 block text-sm font-medium" for="{{ $name }}">{{ $label }}</label>
    <input {{ $attributes->merge(['class' => 'form-input w-full py-2']) }} id="{{ $name }}" name="{{ $name }}" type="{{ $type }}">
</div>
