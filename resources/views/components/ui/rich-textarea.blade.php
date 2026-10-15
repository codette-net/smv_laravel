@props([
    'label',
    'name',
    'required' => false,
    'rows' => 14,
    'value' => null,
])

@php
    $errorMessage = $errors->first($name);
    $inputValue = old($name, $value);
@endphp

<div x-data="richTextEditor">
    <label class="mb-1 block text-sm font-medium" id="{{ $name }}-label" for="{{ $name }}">
        {{ $label }}
        @if ($required)
            <span class="text-red-500" aria-hidden="true">*</span>
        @endif
    </label>

    <div
        class="overflow-hidden rounded-lg border bg-white focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-200"
        :class="{ 'border-red-300': {{ $errorMessage ? 'true' : 'false' }} }"
        x-cloak
        x-show="enhanced"
    >
        <div class="flex flex-wrap items-center gap-1 border-b border-slate-200 bg-slate-50 p-2" role="toolbar" aria-label="Opmaak vacaturebeschrijving">
            <select class="form-select h-9 w-auto py-1 text-sm" aria-label="Tekststijl" @change="format($event)">
                <option value="p">Alinea</option>
                <option value="h2">Kop 2</option>
                <option value="h3">Kop 3</option>
            </select>
            <button class="inline-flex size-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" type="button" title="Vet" aria-label="Vet" @click="run('bold')">
                <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 12h8a4 4 0 0 1 0 8H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h7a4 4 0 0 1 0 8" /></svg>
            </button>
            <button class="inline-flex size-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" type="button" title="Cursief" aria-label="Cursief" @click="run('italic')">
                <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" x2="10" y1="4" y2="4" /><line x1="14" x2="5" y1="20" y2="20" /><line x1="15" x2="9" y1="4" y2="20" /></svg>
            </button>
            <button class="inline-flex size-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" type="button" title="Opsomming" aria-label="Opsomming" @click="run('insertUnorderedList')">
                <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" x2="21" y1="6" y2="6" /><line x1="8" x2="21" y1="12" y2="12" /><line x1="8" x2="21" y1="18" y2="18" /><line x1="3" x2="3.01" y1="6" y2="6" /><line x1="3" x2="3.01" y1="12" y2="12" /><line x1="3" x2="3.01" y1="18" y2="18" /></svg>
            </button>
            <button class="inline-flex size-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" type="button" title="Genummerde lijst" aria-label="Genummerde lijst" @click="run('insertOrderedList')">
                <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="10" x2="21" y1="6" y2="6" /><line x1="10" x2="21" y1="12" y2="12" /><line x1="10" x2="21" y1="18" y2="18" /><path d="M4 6h1v4" /><path d="M4 10h2" /><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1" /></svg>
            </button>
            <button class="inline-flex size-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" type="button" title="Link toevoegen" aria-label="Link toevoegen" @click="addLink()">
                <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" /><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" /></svg>
            </button>
            <button class="inline-flex size-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" type="button" title="Ongedaan maken" aria-label="Ongedaan maken" @click="run('undo')">
                <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14 4 9l5-5" /><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11" /></svg>
            </button>
            <button class="inline-flex size-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" type="button" title="Opnieuw" aria-label="Opnieuw" @click="run('redo')">
                <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 14 5-5-5-5" /><path d="M20 9H9.5a5.5 5.5 0 0 0 0 11H13" /></svg>
            </button>
        </div>
        <div
            class="rich-content prose prose-slate min-h-72 max-w-none p-4 focus:outline-none"
            x-ref="editor"
            contenteditable="true"
            role="textbox"
            aria-multiline="true"
            @if ($required) aria-required="true" @endif
            aria-labelledby="{{ $name }}-label"
            @input="sync()"
            @blur="sync()"
        ></div>
    </div>

    <textarea
        {{ $attributes->class([
            'form-textarea w-full',
            'border-red-300' => $errorMessage,
        ]) }}
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        x-ref="input"
        :class="{ 'sr-only': enhanced }"
        @if ($required) required aria-required="true" :required="! enhanced" @endif
        @if ($errorMessage) aria-invalid="true" aria-describedby="{{ $name }}-error {{ $name }}-help" @else aria-describedby="{{ $name }}-help" @endif
    >{{ $inputValue }}</textarea>

    <p class="mt-2 text-xs leading-5 text-slate-500" id="{{ $name }}-help">
        Gebruik alinea’s, tussenkoppen, vet, cursief, lijsten en veilige links. Andere opmaak wordt bij opslaan verwijderd.
    </p>

    @if ($errorMessage)
        <p class="mt-1 text-xs text-red-500" id="{{ $name }}-error">{{ $errorMessage }}</p>
    @endif
</div>
