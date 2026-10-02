@extends('layouts.public')

@section('title', 'Contact | Sales en Marketing Vacatures')
@section('meta_description', 'Neem contact op over vacatureplaatsing, adverteren en samenwerken met Sales en Marketing Vacatures.')
@section('canonical', route('contact'))

@section('content')
    <div class="flex">
        <div class="w-full md:w-1/2">
            <div class="flex min-h-screen flex-col justify-center">
                <div class="px-5 py-24 sm:px-6">
                    <div class="mx-auto w-full max-w-md">
                        <a class="mb-8 inline-flex" href="{{ route('home') }}" aria-label="Sales en Marketing Vacatures, home">
                            <img src="{{ Vite::asset('resources/images/smv_profile.png') }}" width="32" height="32" alt="Sales en Marketing Vacatures">
                        </a>
                        <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Contact</p>
                        <h1 class="mt-3 font-playfair-display text-4xl text-slate-800">Bespreek uw vacature of samenwerking</h1>
                        <p class="mt-5 text-lg leading-8 text-slate-600">Wilt u een sales- of marketingvacature plaatsen, meerdere functies bespreken of weten welke zichtbaarheid past? Neem rechtstreeks contact met ons op.</p>

                        <dl class="mt-8 space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div>
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

                        <p class="mt-6 text-sm leading-6 text-slate-500">Het openbare contactformulier wordt nog ingericht. E-mail en telefoon zijn nu de beschikbare contactroutes.</p>
                        <div class="mt-8 flex flex-wrap gap-3">
                            <a class="btn bg-blue-600 text-white hover:bg-blue-700" href="mailto:sales@salesenmarketingvacatures.nl">Stuur een e-mail</a>
                            <a class="btn border border-slate-300 bg-white text-slate-800 hover:bg-slate-50" href="{{ route('advertising') }}">Bekijk adverteren</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="relative hidden bg-slate-900 md:block md:w-1/2" aria-hidden="true">
            <img class="absolute inset-0 h-full w-full object-cover opacity-15" src="{{ asset('images/request-demo-bg.jpg') }}" width="760" height="900" alt="">
            <div class="relative flex min-h-screen items-center">
                <div class="mx-auto max-w-lg px-6">
                    <h2 class="font-playfair-display text-4xl text-slate-100">Sales en Marketing Vacatures</h2>
                    <p class="mt-4 text-lg italic text-slate-300">Gericht zichtbaar bij professionals die bewust voor sales en marketing kiezen.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
