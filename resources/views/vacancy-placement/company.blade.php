@extends('layouts.public')

@section('title', 'Bedrijf toevoegen | Sales en Marketing Vacatures')
@section('canonical', route('vacancy-placement.index'))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="mx-auto max-w-4xl px-4 pb-16 pt-28 sm:px-6 md:pt-36">
        <x-vacancy-placement.steps :current="2" />
        <form class="mx-auto mt-10 max-w-2xl rounded-xl border border-slate-200 bg-white p-7 shadow-sm sm:p-9" action="{{ route('vacancy-placement.company.store') }}" method="POST">
            @csrf
            <h1 class="font-playfair-display text-3xl font-bold text-slate-900">Voeg uw bedrijf toe</h1>
            <p class="mt-3 leading-7 text-slate-600">We maken een minimaal bedrijfsprofiel aan. SMV controleert het profiel voordat het publiek zichtbaar kan worden.</p>
            <div class="mt-7 space-y-5">
                <x-ui.input name="name" label="Bedrijfsnaam" autocomplete="organization" required />
                <x-ui.input name="email" label="Zakelijk e-mailadres (optioneel)" type="email" autocomplete="email" />
                <x-ui.input name="phone" label="Telefoonnummer (optioneel)" type="tel" autocomplete="tel" />
                <x-ui.input name="location" label="Vestigingsplaats (optioneel)" autocomplete="address-level2" />
            </div>
            <div class="mt-7 flex justify-end"><x-ui.button type="submit" variant="brand">Verder naar vacaturegegevens</x-ui.button></div>
        </form>
    </section>
@endsection
