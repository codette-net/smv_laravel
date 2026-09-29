@props(['name', 'label', 'rows' => 4])

<div>
    <label class="mb-1 block text-sm font-medium" for="{{ $name }}">{{ $label }}</label>
    <textarea {{ $attributes->merge(['class' => 'form-textarea w-full py-2']) }} id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}"></textarea>
</div>
