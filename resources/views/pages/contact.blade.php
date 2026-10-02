@extends('layouts.public')

@section('title', 'Contact | Sales en Marketing Vacatures')
@section('meta_description', 'Neem contact op over vacatureplaatsing, adverteren en samenwerken met Sales en Marketing Vacatures.')
@section('canonical', route('contact'))

@section('content')
    <div class="flex">
        <div class="w-full md:w-3/5">
            <div class="flex min-h-screen flex-col justify-center">
                <div class="px-5 py-24 sm:px-6">
                    <div class="mx-auto w-full max-w-2xl">
                        <a class="mb-8 inline-flex" href="{{ route('home') }}" aria-label="Sales en Marketing Vacatures, home">
                            <img src="{{ Vite::asset('resources/images/smv_profile.png') }}" width="32" height="32" alt="Sales en Marketing Vacatures">
                        </a>
                        <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Contact</p>
                        <h1 class="mt-3 font-playfair-display text-4xl text-slate-800">Bespreek uw vacature of samenwerking</h1>
                        <p class="mt-5 text-lg leading-8 text-slate-600">Wilt u een sales- of marketingvacature plaatsen, meerdere functies bespreken of weten welke zichtbaarheid past? Neem rechtstreeks contact met ons op.</p>

                        <dl class="mt-8 grid gap-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-3">
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-semibold text-slate-900">E-mail</dt>
                                <dd class="mt-1"><a class="font-semibold text-blue-700 transition hover:text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="mailto:sales@salesenmarketingvacatures.nl">sales@salesenmarketingvacatures.nl</a></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-semibold text-slate-900">Telefoon</dt>
                                <dd class="mt-1"><a class="font-semibold text-blue-700 transition hover:text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="tel:+31630852152">06 30852152</a></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-semibold text-slate-900">Reactietijd</dt>
                                <dd class="mt-1 text-slate-600">Op werkdagen proberen we binnen 24 uur te reageren.</dd>
                            </div>
                        </dl>

                        @if (session('contact_error'))
                            <div class="mt-8 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert">
                                {{ session('contact_error') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="mt-8 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert" aria-labelledby="contact-errors-title">
                                <p class="font-semibold" id="contact-errors-title">Controleer de gemarkeerde velden.</p>
                                <ul class="mt-2 list-disc space-y-1 pl-5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form class="mt-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" action="{{ route('contact.store') }}" method="POST">
                            @csrf
                            <h2 class="text-xl font-semibold text-slate-900">Stuur ons een bericht</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Velden met een sterretje zijn verplicht.</p>

                            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                                <x-ui.input name="name" label="Naam" autocomplete="name" required />
                                <x-ui.input name="email" label="E-mailadres" type="email" autocomplete="email" required />
                                <div class="sm:col-span-2">
                                    <x-ui.select-dropdown name="purpose" label="Onderwerp / reden van contact" :options="$contactPurposes" required />
                                </div>
                                <x-ui.input name="company" label="Bedrijfsnaam (optioneel)" autocomplete="organization" />
                                <x-ui.input name="phone" label="Telefoonnummer (optioneel)" type="tel" autocomplete="tel" placeholder="Bijvoorbeeld +31 6 12345678" />
                                <div class="sm:col-span-2">
                                    <x-ui.textarea name="message" label="Bericht" rows="7" required placeholder="Waar kunnen we u mee helpen?" />
                                </div>
                            </div>

                            <div class="absolute -left-[10000px] top-auto size-px overflow-hidden" aria-hidden="true">
                                <label for="website">Website</label>
                                <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
                                <p class="max-w-md text-xs leading-5 text-slate-500">We gebruiken uw gegevens alleen om uw vraag te beantwoorden.</p>
                                <x-ui.button type="submit" variant="brand">Bericht versturen</x-ui.button>
                            </div>
                        </form>

                        <p class="mt-6 text-sm text-slate-600">Wilt u meer weten over vacatureplaatsing? <a class="font-semibold text-blue-700 hover:text-blue-800" href="{{ route('advertising') }}">Bekijk de mogelijkheden voor adverteren</a>.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="relative hidden bg-slate-900 md:block md:w-2/5" aria-hidden="true">
            <img class="absolute inset-0 h-full w-full object-cover opacity-15" src="{{ asset('images/request-demo-bg.jpg') }}" width="760" height="900" alt="">
            <div class="relative flex min-h-screen items-center">
                <div class="mx-auto max-w-lg px-6">
                    <h2 class="font-playfair-display text-4xl text-slate-100">Sales en Marketing Vacatures</h2>
                    <p class="mt-4 text-lg italic text-slate-300">Gericht zichtbaar bij professionals die bewust voor sales en marketing kiezen.</p>
                </div>
            </div>
        </div>
    </div>

    <x-ui.status-modal
        id="contact-success"
        title="Bericht verzonden"
        :description="session('contact_success', '')"
        :open="session()->has('contact_success')"
    />
@endsection
