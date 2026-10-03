@extends('layouts.public')

@section('title', 'Bedrijfsprofiel bewerken | Sales en Marketing Vacatures')
@section('canonical', route('account.companies.edit', $company))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="mx-auto max-w-4xl px-4 pb-16 pt-28 sm:px-6 md:pt-36">
        <a class="text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('account.index') }}">← Mijn account</a>
        <h1 class="mt-3 font-playfair-display text-3xl font-bold text-slate-900">Bedrijfsprofiel bewerken</h1>
        <p class="mt-3 max-w-2xl leading-7 text-slate-600">Deze gegevens worden gebruikt op de publieke bedrijfspagina zodra uw bedrijf actief is. Status, eigenaar en uitlichting worden door SMV beheerd.</p>

        <form class="mt-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-9" action="{{ route('account.companies.update', $company) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            @if ($errors->any())
                <div class="mb-7 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert">
                    <p class="font-semibold">Controleer de gemarkeerde velden.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2"><x-ui.input name="name" label="Bedrijfsnaam" :value="$company->name" required /></div>
                <div class="sm:col-span-2"><x-ui.input name="tagline" label="Korte introductie" :value="$company->tagline" /></div>
                <div class="sm:col-span-2"><x-ui.textarea name="description" label="Bedrijfsomschrijving" :value="$company->description" rows="8" /></div>
                <x-ui.input name="location" label="Locatie" :value="$company->location" />
                <x-ui.input name="website" label="Website" type="url" placeholder="https://" :value="$company->website" />
                <x-ui.input name="email" label="Publiek contact-e-mailadres" type="email" :value="$company->email" />
                <x-ui.input name="phone" label="Publiek telefoonnummer" type="tel" :value="$company->phone" />
                <x-ui.input name="linkedin_url" label="LinkedIn" type="url" placeholder="https://" :value="$company->linkedin_url" />
                <x-ui.input name="facebook_url" label="Facebook" type="url" placeholder="https://" :value="$company->facebook_url" />
                <div class="sm:col-span-2"><x-ui.input name="instagram_url" label="Instagram" type="url" placeholder="https://" :value="$company->instagram_url" /></div>

                <div>
                    <label class="mb-1 block text-sm font-medium" for="logo">Logo</label>
                    <input class="form-input w-full" id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp">
                    <p class="mt-2 text-xs text-slate-500">JPG, PNG of WebP, maximaal 5 MB. Het logo wordt volledig passend weergegeven.</p>
                    @error('logo')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium" for="cover">Omslagafbeelding</label>
                    <input class="form-input w-full" id="cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp">
                    <p class="mt-2 text-xs text-slate-500">JPG, PNG of WebP, maximaal 5 MB. De afbeelding wordt beeldvullend gebruikt.</p>
                    @error('cover')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <a class="btn justify-center border border-slate-300 bg-white text-slate-800 hover:bg-slate-50" href="{{ route('account.index') }}">Annuleren</a>
                <x-ui.button type="submit" variant="brand">Bedrijfsprofiel opslaan</x-ui.button>
            </div>
        </form>
    </section>
@endsection
