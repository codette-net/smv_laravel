@extends('layouts.public')

@section('title', 'Vacatures | Sales en Marketing Vacatures')
@section('meta_description', 'Vind actuele sales- en marketingvacatures bij toonaangevende werkgevers.')
@section('canonical', $seoCanonical)
@section('robots', $seoRobots)

@section('content')
    <section class="mx-auto max-w-7xl px-4 sm:px-6">
        <div class="py-10 md:py-16">
            <div class="mb-10 max-w-2xl">
                <p class="mb-3 text-sm font-semibold uppercase tracking-widest text-blue-600">Sales &amp; Marketing Vacatures</p>
                <h1 class="font-inter text-3xl font-bold text-gray-800 md:text-4xl">Vind jouw volgende vacature</h1>
                <p class="mt-3 text-lg text-gray-500">Ontdek actuele vacatures bij werkgevers die commercieel talent zoeken.</p>
            </div>

            <div class="lg:flex lg:items-start lg:gap-10">
                <aside class="mb-8 lg:sticky lg:top-24 lg:mb-0 lg:w-96 lg:shrink-0">
                    <div class="sticky top-8 rounded-xl border border-gray-200 bg-gray-50 p-5">
                        <div class="mb-5 flex items-center justify-between">
                            <h2 class="text-lg font-bold text-gray-800">Verfijn je zoekopdracht</h2>
                        </div>
                        <x-vacancy.filter-form :filters="$filters" :sort="$sort" :sort-options="$sortOptions" :locations="$locations" :taxonomy-options="$taxonomyOptions" :companies="$companies" :has-filters="$hasFilters" :has-additional-filters="$hasAdditionalFilters" :secondary-filter-count="$secondaryFilterCount" :filter-errors="$filterErrors" />
                    </div>
                </aside>

                @include('vacancies.partials.search-results')
            </div>
        </div>
    </section>
@endsection
