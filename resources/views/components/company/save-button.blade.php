@props([
    'company',
    'saved' => (bool) ($company->is_saved ?? false),
])

@php
    $saveLabel = 'Bewaar bedrijf';
    $unsaveLabel = 'Verwijder uit bewaarde bedrijven';
    $label = $saved ? $unsaveLabel : $saveLabel;
@endphp

<form
    method="POST"
    action="{{ $saved ? route('companies.unsave', $company) : route('companies.save', $company) }}"
    x-data="savedItemToggle({
        saved: @js($saved),
        saveUrl: @js(route('companies.save', $company)),
        unsaveUrl: @js(route('companies.unsave', $company)),
        saveLabel: @js($saveLabel),
        unsaveLabel: @js($unsaveLabel),
    })"
    x-bind:action="saved ? unsaveUrl : saveUrl"
    @auth
        x-on:submit.prevent="toggle"
    @endauth
>
    @csrf
    <input type="hidden" name="_method" value="{{ $saved ? 'DELETE' : 'POST' }}" x-bind:value="saved ? 'DELETE' : 'POST'">

    <x-ui.tooltip :text="$label" align="right" dynamic-text="label">
        <button
            type="submit"
            class="inline-flex size-9 items-center justify-center rounded-full border bg-white p-2 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60 data-[saved=false]:border-slate-200 data-[saved=false]:text-slate-500 data-[saved=false]:hover:border-blue-300 data-[saved=false]:hover:text-blue-700 data-[saved=true]:border-blue-200 data-[saved=true]:text-blue-700 data-[saved=true]:hover:bg-blue-50"
            data-saved="{{ $saved ? 'true' : 'false' }}"
            x-bind:data-saved="saved.toString()"
            x-bind:disabled="pending"
            x-bind:aria-label="label"
            x-bind:aria-pressed="saved.toString()"
            aria-label="{{ $label }}"
            aria-pressed="{{ $saved ? 'true' : 'false' }}"
        >
            <svg class="size-4" viewBox="0 0 24 24" fill="{{ $saved ? 'currentColor' : 'none' }}" x-bind:fill="saved ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c.1.128.157.286.157.448v17.006l-5.19-3.708a.96.96 0 0 0-1.12 0l-5.19 3.708V3.77c0-.162.055-.32.157-.448a.75.75 0 0 1 .593-.282h10a.75.75 0 0 1 .593.282Z" />
            </svg>
        </button>
    </x-ui.tooltip>

    <span class="sr-only" role="status" x-cloak x-show="failed">Opslaan is niet gelukt. Probeer het opnieuw.</span>
</form>
