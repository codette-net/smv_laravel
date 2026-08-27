@extends('layouts.public')

@section('title', $blogPost->title.' | Sales en Marketing Vacatures')
@section('meta_description', $metaDescription)
@section('canonical', route('blog.show', $blogPost))
@section('og_type', 'article')
@push('structured_data')
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:py-14">
        <a class="inline-flex text-sm font-semibold text-blue-700 hover:text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ route('blog.index') }}">← Terug naar blog</a>
        <header class="mt-7 border-b border-slate-200 pb-7">
            @if ($blogPost->published_at)
                <time class="text-sm text-slate-500" datetime="{{ $blogPost->published_at->toDateString() }}">{{ $blogPost->published_at->translatedFormat('j F Y') }}</time>
            @endif
            @if ($blogPost->categories->isNotEmpty() || $blogPost->tags->where('type', 'blog')->isNotEmpty())
                <div class="mt-4 flex flex-wrap gap-2 text-sm font-semibold">
                    @foreach ($blogPost->categories as $category)
                        <a class="rounded-full bg-blue-50 px-3 py-1 text-blue-700 transition hover:bg-blue-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" href="{{ route('blog.categories.show', ['blogCategory' => $category->slug]) }}">{{ $category->name }}</a>
                    @endforeach
                    @foreach ($blogPost->tags->where('type', 'blog') as $tag)
                        <a class="rounded-full bg-slate-100 px-3 py-1 text-slate-600 transition hover:bg-slate-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" href="{{ route('blog.tags.show', ['blogTag' => $tag->slug]) }}">#{{ $tag->name }}</a>
                    @endforeach
                </div>
            @endif
            <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ $blogPost->title }}</h1>
            @if ($blogPost->excerpt)
                <p class="mt-4 text-xl leading-8 text-slate-600">{{ $blogPost->excerpt }}</p>
            @endif
        </header>
        @if ($featuredImageUrl)
            <img class="mt-8 aspect-[16/9] w-full rounded-xl object-cover" src="{{ $featuredImageUrl }}" alt="Omslagafbeelding bij {{ $blogPost->title }}" loading="eager">
        @endif
        <div class="prose prose-slate prose-headings:text-slate-900 prose-a:font-semibold prose-a:text-blue-700 prose-a:no-underline hover:prose-a:text-blue-800 prose-blockquote:border-blue-500 prose-blockquote:text-slate-600 prose-img:rounded-xl mt-8 max-w-none leading-7">{!! $blogPost->content !!}</div>

        @if ($relatedVacancies->isNotEmpty())
            <section class="mt-12 border-t border-slate-200 pt-8" aria-labelledby="gerelateerde-vacatures">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Gerelateerd</p>
                        <h2 class="mt-1 text-2xl font-bold text-slate-900" id="gerelateerde-vacatures">Relevante vacatures</h2>
                    </div>
                    <a class="font-semibold text-blue-700 transition hover:text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ route('vacancies.index') }}">Bekijk alle vacatures</a>
                </div>
                <div class="mt-6 space-y-4">
                    @foreach ($relatedVacancies as $vacancy)
                        <x-vacancy.card :vacancy="$vacancy" :detail-url="route('vacancies.show', $vacancy)" />
                    @endforeach
                </div>
            </section>
        @endif

        @if ($relatedCompanies->isNotEmpty())
            <section class="mt-12 border-t border-slate-200 pt-8" aria-labelledby="gerelateerde-bedrijven">
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Gerelateerd</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-900" id="gerelateerde-bedrijven">Gerelateerde bedrijven</h2>
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($relatedCompanies as $company)
                        <x-company.card :company="$company" :logo-url="$company->publicLogoUrl()" />
                    @endforeach
                </div>
            </section>
        @endif
    </article>
@endsection
