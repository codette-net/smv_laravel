@extends('layouts.public')

@section('title', 'Sales en Marketing Vacatures | Vind jouw volgende commerciële baan')
@section('meta_description', 'Vind actuele sales- en marketingvacatures en ontdek werkgevers die commercieel talent zoeken.')
@section('canonical', route('home'))

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Sales &amp; Marketing Vacatures</p>
            <h1 class="mt-3 max-w-3xl text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">Vind jouw volgende baan in sales of marketing</h1>
            <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-600">Bekijk actuele vacatures en maak kennis met werkgevers die op zoek zijn naar commercieel talent.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a class="btn bg-blue-600 text-white hover:bg-blue-700" href="{{ route('vacancies.index') }}">Bekijk vacatures</a>
                <a class="btn border border-slate-300 bg-white text-slate-700 hover:border-slate-400" href="{{ route('companies.index') }}">Bekijk bedrijven</a>
            </div>
        </div>
    </section>
@endsection
