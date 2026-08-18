@extends('layouts.public')

@section('title', 'Sollicitatie ontvangen | Sales en Marketing Vacatures')

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-16 text-center sm:px-6">
        <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
            <h1 class="text-3xl font-bold text-slate-900">Bedankt voor je sollicitatie</h1>
            <p class="mt-4 text-slate-600">Je sollicitatie voor <strong>{{ $vacancy->title }}</strong> is ontvangen.</p>
            <a class="btn mt-7 bg-slate-900 text-white hover:bg-slate-800" href="{{ route('vacancies.index') }}">Bekijk meer vacatures</a>
        </div>
    </section>
@endsection
