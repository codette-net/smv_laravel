@extends('layouts.public')

@section('title', $company->name.' | Sales en Marketing Vacatures')
@section('meta_description', $metaDescription)

@section('content')
    <section class="relative overflow-hidden bg-slate-900">
        <div class="absolute inset-0">
            @if ($coverUrl)
                <img class="h-full w-full object-cover opacity-45" src="{{ $coverUrl }}" alt="">
            @else
                <div class="h-full w-full bg-linear-to-br from-blue-700 via-slate-900 to-slate-950"></div>
            @endif
        </div>
        <div class="relative mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-end">
                <div class="flex h-28 w-28 shrink-0 items-center justify-center overflow-hidden rounded-2xl border-4 border-white bg-white text-3xl font-bold text-blue-700 shadow-lg">
                    @if ($logoUrl)
                        <img class="h-full w-full object-cover" src="{{ $logoUrl }}" alt="Logo van {{ $company->name }}">
                    @else
                        {{ Str::upper(Str::substr($company->name, 0, 1)) }}
                    @endif
                </div>
                <div class="max-w-3xl text-white">
                    @if ($company->is_featured)
                        <span class="inline-flex rounded-full bg-amber-300 px-3 py-1 text-xs font-bold uppercase tracking-wide text-amber-950">Uitgelicht bedrijf</span>
                    @endif
                    <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">{{ $company->name }}</h1>
                    @if ($company->tagline)
                        <p class="mt-3 text-lg text-slate-200">{{ $company->tagline }}</p>
                    @endif
                    @if ($company->location)
                        <p class="mt-3 text-sm font-medium text-slate-300">{{ $company->location }}</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[minmax(0,1fr)_19rem] lg:px-8 lg:py-14">
        <div class="space-y-10">
            @if ($company->description)
                <section aria-labelledby="over-het-bedrijf">
                    <h2 id="over-het-bedrijf" class="text-2xl font-bold text-slate-900">Over het bedrijf</h2>
                    <div class="mt-4 whitespace-pre-line leading-7 text-slate-600">{{ $company->description }}</div>
                </section>
            @endif

            <section aria-labelledby="vacatures">
                <div class="flex items-baseline justify-between gap-4">
                    <h2 id="vacatures" class="text-2xl font-bold text-slate-900">Vacatures bij {{ $company->name }}</h2>
                    <span class="text-sm text-slate-500">{{ $vacancies->count() }} {{ Str::plural('vacature', $vacancies->count()) }}</span>
                </div>
                @if ($vacancies->isNotEmpty())
                    <div class="mt-5 space-y-3">
                        @foreach ($vacancies as $vacancy)
                            <x-company.vacancy-card :vacancy="$vacancy" :logo-url="$logoUrl" />
                        @endforeach
                    </div>
                @else
                    <div class="mt-5 rounded-xl border border-dashed border-slate-300 bg-white p-6 text-slate-600">
                        Momenteel geen openstaande vacatures.
                    </div>
                @endif
            </section>
        </div>

        @if ($company->website || $company->email || $company->phone || $company->linkedin_url || $company->facebook_url || $company->instagram_url || $company->video_url || $company->categories->isNotEmpty())
            <aside class="self-start rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
                <h2 class="text-lg font-bold text-slate-900">Contact</h2>
                <div class="mt-4 space-y-3 text-sm">
                    @if ($company->website)
                        <a class="block font-semibold text-blue-700 hover:text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ $company->website }}" target="_blank" rel="noopener noreferrer">Bezoek website</a>
                    @endif
                    @if ($company->email)
                        <a class="block text-slate-600 hover:text-blue-700 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="mailto:{{ $company->email }}">{{ $company->email }}</a>
                    @endif
                    @if ($company->phone)
                        <a class="block text-slate-600 hover:text-blue-700 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="tel:{{ $company->phone }}">{{ $company->phone }}</a>
                    @endif
                    @foreach (['linkedin_url' => 'LinkedIn', 'facebook_url' => 'Facebook', 'instagram_url' => 'Instagram', 'video_url' => 'Video'] as $field => $label)
                        @if ($company->{$field})
                            <a class="block text-slate-600 hover:text-blue-700 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ $company->{$field} }}" target="_blank" rel="noopener noreferrer">{{ $label }}</a>
                        @endif
                    @endforeach
                </div>
                @if ($company->categories->isNotEmpty())
                    <div class="mt-6 border-t border-slate-200 pt-5">
                        <h3 class="text-sm font-semibold text-slate-900">Categorieën</h3>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($company->categories as $category)
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">{{ $category->name }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </aside>
        @endif
    </div>
@endsection
