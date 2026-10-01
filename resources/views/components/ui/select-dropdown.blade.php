@props([
    'error' => null,
    'id' => null,
    'label',
    'name',
    'options' => [],
    'placeholder' => 'Selecteer een optie',
    'required' => false,
    'value' => null,
])

@php
    $id ??= $name;
    $options = collect($options)->mapWithKeys(fn ($optionLabel, $optionValue) => [(string) $optionValue => (string) $optionLabel]);
    $currentValue = (string) old($name, $value ?? '');
    $selectedLabel = $options->get($currentValue, $placeholder);
    $errorMessage = $error ?: $errors->first($name);
    $menuId = $id.'-options';
@endphp

<div>
    <label class="block text-sm font-medium mb-1" for="{{ $id }}" id="{{ $id }}-label">
        {{ $label }}
        @if ($required)
            <span class="text-red-500" aria-hidden="true">*</span>
        @endif
    </label>
    <div class="relative inline-flex w-full" x-data="{ open: false, value: @js($currentValue), label: @js($selectedLabel) }">
        <input
            name="{{ $name }}"
            type="hidden"
            x-model="value"
            x-ref="input"
            @if ($required) required @endif
            {{ $attributes->whereStartsWith('x-') }}
        >
        <button
            class="btn w-full justify-between min-w-44 bg-white border-gray-200 hover:border-gray-300 text-gray-600 hover:text-gray-800"
            id="{{ $id }}"
            type="button"
            aria-haspopup="listbox"
            aria-labelledby="{{ $id }}-label {{ $id }}"
            aria-controls="{{ $menuId }}"
            @if ($required) aria-required="true" @endif
            @if ($errorMessage) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            :aria-expanded="open"
            x-ref="trigger"
            x-on:click.prevent="open = ! open"
            x-on:keydown.arrow-down.prevent="open = true; $nextTick(() => $refs.options.querySelector('button')?.focus())"
            x-on:keydown.enter.prevent="open = ! open"
            x-on:keydown.space.prevent="open = ! open"
        >
            <span class="flex min-w-0 items-center">
                <span class="truncate" x-text="label"></span>
            </span>
            <svg class="shrink-0 ml-1 fill-current text-gray-400" width="11" height="7" viewBox="0 0 11 7" aria-hidden="true">
                <path d="M5.4 6.8 0 1.4 1.4 0l4 4 4-4 1.4 1.4z" />
            </svg>
        </button>
        <div
            class="z-20 absolute top-full left-0 w-full bg-white border border-gray-200 py-1.5 rounded-lg inset-shadow-md overflow-y-auto max-h-64 mt-1"
            id="{{ $menuId }}"
            role="listbox"
            aria-labelledby="{{ $id }}-label"
            x-ref="options"
            x-on:click.outside="open = false"
            x-on:keydown.escape.window="if (open) { open = false; $refs.trigger.focus() }"
            x-show="open"
            x-transition:enter="transition ease-out duration-100 transform"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-out duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-cloak
        >
            <div class="font-medium text-sm text-gray-600 divide-y divide-gray-200">
                <button
                    class="flex items-center justify-between w-full hover:bg-gray-50 py-2 px-3 cursor-pointer"
                    type="button"
                    role="option"
                    :class="value === '' && 'text-violet-500'"
                    :aria-selected="value === ''"
                    x-on:click="value = ''; $refs.input.value = ''; label = @js($placeholder); open = false; $refs.input.dispatchEvent(new Event('change', { bubbles: true })); $nextTick(() => $refs.trigger.focus())"
                    x-on:keydown.arrow-down.prevent="$el.nextElementSibling?.focus()"
                    x-on:keydown.home.prevent="$el.parentElement.firstElementChild.focus()"
                    x-on:keydown.end.prevent="$el.parentElement.lastElementChild.focus()"
                >
                    <span>{{ $placeholder }}</span>
                    <svg class="shrink-0 ml-2 fill-current text-violet-400" :class="value !== '' && 'invisible'" width="12" height="9" viewBox="0 0 12 9" aria-hidden="true"><path d="m10.28.28-6.291 6.295L1.695 4.28A1 1 0 0 0 .28 5.695l3 3a1 1 0 0 0 1.414 0l7-7A1 1 0 0 0 10.28.28Z" /></svg>
                </button>
                @foreach ($options as $optionValue => $optionLabel)
                    <button
                        class="flex items-center justify-between w-full hover:bg-gray-50 py-2 px-3 cursor-pointer"
                        type="button"
                        role="option"
                        :class="value === @js($optionValue) && 'text-violet-500'"
                        :aria-selected="value === @js($optionValue)"
                        x-on:click="value = @js($optionValue); $refs.input.value = @js($optionValue); label = @js($optionLabel); open = false; $refs.input.dispatchEvent(new Event('change', { bubbles: true })); $nextTick(() => $refs.trigger.focus())"
                        x-on:keydown.arrow-down.prevent="$el.nextElementSibling?.focus()"
                        x-on:keydown.arrow-up.prevent="$el.previousElementSibling?.focus()"
                        x-on:keydown.home.prevent="$el.parentElement.firstElementChild.focus()"
                        x-on:keydown.end.prevent="$el.parentElement.lastElementChild.focus()"
                    >
                        <span class="text-left">{{ $optionLabel }}</span>
                        <svg class="shrink-0 ml-2 fill-current text-violet-400" :class="value !== @js($optionValue) && 'invisible'" width="12" height="9" viewBox="0 0 12 9" aria-hidden="true"><path d="m10.28.28-6.291 6.295L1.695 4.28A1 1 0 0 0 .28 5.695l3 3a1 1 0 0 0 1.414 0l7-7A1 1 0 0 0 10.28.28Z" /></svg>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
    @if ($errorMessage)
        <p class="text-xs mt-1 text-red-500" id="{{ $id }}-error">{{ $errorMessage }}</p>
    @endif
</div>
